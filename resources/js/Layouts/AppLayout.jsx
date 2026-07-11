import { Link, router, usePage, Head } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';
import { Toaster, toast } from 'sonner';
import axios from 'axios';
import {
    Menu,
    WifiOff,
    PanelLeftClose,
    PanelLeftOpen,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { playNotificationSound } from '@/Services/audio';

import SidebarContent from './Partials/SidebarContent';
import NotificationDropdown from './Partials/NotificationDropdown';
import UserMenu from './Partials/UserMenu';

export default function AppLayout({ title, header, children }) {
    const { auth, brandContext, flash, app } = usePage().props;
    const user = auth?.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    const [isCollapsed, setIsCollapsed] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('sidebar-collapsed') === 'true';
        }
        return false;
    });

    const toggleSidebar = () => {
        setIsCollapsed((prev) => {
            const next = !prev;
            localStorage.setItem('sidebar-collapsed', String(next));
            return next;
        });
    };

    const [notifications, setNotifications] = useState(user?.recent_notifications || []);
    const [unreadCount, setUnreadCount] = useState(user?.unread_notifications_count || 0);

    // PWA & Offline Status States
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [isInstallable, setIsInstallable] = useState(false);
    const [isOffline, setIsOffline] = useState(!navigator.onLine);

    useEffect(() => {
        const handleBeforeInstallPrompt = (e) => {
            e.preventDefault();
            setDeferredPrompt(e);
            setIsInstallable(true);
        };
        const handleAppInstalled = () => {
            setDeferredPrompt(null);
            setIsInstallable(false);
            toast.success('Aplikasi berhasil diinstal!');
        };
        const goOnline = () => {
            setIsOffline(false);
            toast.dismiss('offline-toast');
            toast.success('Koneksi internet terhubung kembali.');
        };
        const goOffline = () => {
            setIsOffline(true);
            toast.error('Koneksi internet terputus. Menjalankan mode offline.', {
                duration: Infinity,
                id: 'offline-toast'
            });
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);
        window.addEventListener('online', goOnline);
        window.addEventListener('offline', goOffline);

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
            window.removeEventListener('appinstalled', handleAppInstalled);
            window.removeEventListener('online', goOnline);
            window.removeEventListener('offline', goOffline);
        };
    }, []);

    const handleInstallClick = async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`PWA Install Choice Outcome: ${outcome}`);
        setDeferredPrompt(null);
        setIsInstallable(false);
    };

    // Request Browser Desktop Notification permission
    useEffect(() => {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }, []);

    // Multi-tab synchronization channel
    const syncChannel = useRef(null);
    const handledNotifs = useRef(new Set());

    useEffect(() => {
        if (typeof window !== 'undefined') {
            syncChannel.current = new window.BroadcastChannel('protrack_notifications');
            
            const handleSyncMsg = (e) => {
                if (e.data && e.data.type === 'NOTIF_HANDLED') {
                    handledNotifs.current.add(e.data.id);
                }
            };
            
            syncChannel.current.addEventListener('message', handleSyncMsg);
            return () => {
                syncChannel.current?.removeEventListener('message', handleSyncMsg);
                syncChannel.current?.close();
            };
        }
    }, []);

    // Core function to execute dynamic sound and OS Native alert
    const handleNewNotification = (notif) => {
        // Deduplicate locally in React state
        setNotifications((prev) => {
            if (prev.some(existing => existing.id === notif.id)) return prev;
            return [notif, ...prev.slice(0, 9)];
        });
        setUnreadCount((prev) => prev + 1);

        // Check if this notification has already been handled by another tab
        if (handledNotifs.current.has(notif.id)) {
            return;
        }

        // Mark as handled locally and broadcast to other open tabs
        handledNotifs.current.add(notif.id);
        syncChannel.current?.postMessage({ type: 'NOTIF_HANDLED', id: notif.id });

        if (notif.sound) {
            playNotificationSound(notif.sound);
        }

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notif.title, {
                body: notif.body,
                icon: '/favicon.ico'
            });
        }

        toast.info(notif.title, {
            description: notif.body,
            action: notif.action_url ? {
                label: 'Lihat',
                onClick: () => router.visit(notif.action_url)
            } : null
        });
    };

    // WebSocket broadcaster listener + Slow Polling Fallback Sync
    const notificationsRef = useRef(notifications);
    notificationsRef.current = notifications;
    const unreadCountRef = useRef(unreadCount);
    unreadCountRef.current = unreadCount;
    const mountTime = useRef(new Date(Date.now() - 30000)); // 30 seconds buffer for clock drift

    // Sync initial state if it changes in Inertia
    useEffect(() => {
        if (user) {
            setNotifications(user.recent_notifications || []);
            setUnreadCount(user.unread_notifications_count || 0);
        }
    }, [user?.recent_notifications, user?.unread_notifications_count]);

    useEffect(() => {
        if (!user) return;

        let channel = null;
        let isEchoConnected = false;

        if (window.Echo) {
            channel = window.Echo.private(`App.Models.User.${user.id}`)
                .notification((notification) => {
                    isEchoConnected = true;
                    handleNewNotification({
                        id: notification.id,
                        title: notification.title,
                        body: notification.body,
                        no_po: notification.no_po,
                        action_url: notification.action_url,
                        sound: notification.sound,
                        is_read: false,
                        created_at: new Date().toISOString(),
                    });
                });
        }

        // 15 seconds polling fallback sync (skipped if Echo receives messages)
        const interval = setInterval(() => {
            if (isEchoConnected) return;

            axios.get(route('notifications.index'))
                .then((res) => {
                    const latest = res.data.notifications?.data || [];
                    const serverUnread = res.data.unread_count ?? 0;
                    const currentNotifs = notificationsRef.current;
                    const currentUnread = unreadCountRef.current;
                    
                    if (latest.length > 0 && latest[0].id !== currentNotifs[0]?.id) {
                        const newNotifs = latest.filter(n => {
                            const isNew = !currentNotifs.some(existing => existing.id === n.id);
                            const createdTime = new Date(n.created_at);
                            return isNew && createdTime >= mountTime.current;
                        });
                        if (newNotifs.length > 0) {
                            newNotifs.reverse().forEach((n) => {
                                handleNewNotification(n);
                            });
                        }
                    }
                    
                    // Only update state if the values have actually changed to prevent unnecessary re-renders
                    if (serverUnread !== currentUnread) {
                        setUnreadCount(serverUnread);
                    }
                    
                    const isDifferent = latest.length !== currentNotifs.length || 
                        latest.some((n, index) => n.id !== currentNotifs[index]?.id || n.is_read !== currentNotifs[index]?.is_read);
                        
                    if (isDifferent && latest.length > 0) {
                        setNotifications(latest.slice(0, 10));
                    }
                })
                .catch((err) => console.debug('Polling notifications skipped or offline:', err));
        }, 15000);

        return () => {
            if (channel && window.Echo) {
                window.Echo.leave(`App.Models.User.${user.id}`);
            }
            clearInterval(interval);
        };
    }, [user?.id]);

    const markAsRead = (id) => {
        axios.post(route('notifications.read', id))
            .then((res) => {
                setNotifications((prev) => 
                    prev.map((n) => n.id === id ? { ...n, is_read: true } : n)
                );
                if (res.data.unread_count !== undefined) {
                    setUnreadCount(res.data.unread_count);
                } else {
                    setUnreadCount((prev) => Math.max(0, prev - 1));
                }
            })
            .catch((err) => console.error('Failed to mark notification as read:', err));
    };

    const markAllAsRead = () => {
        axios.post(route('notifications.read-all'))
            .then(() => {
                setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
                setUnreadCount(0);
                toast.success('Semua notifikasi ditandai telah dibaca.');
            })
            .catch((err) => console.error('Failed to mark all as read:', err));
    };

    const deleteNotification = (id) => {
        axios.delete(route('notifications.destroy', id))
            .then((res) => {
                const wasUnread = !notifications.find(n => n.id === id)?.is_read;
                setNotifications((prev) => prev.filter((n) => n.id !== id));
                if (res.data.unread_count !== undefined) {
                    setUnreadCount(res.data.unread_count);
                } else if (wasUnread) {
                    setUnreadCount((prev) => Math.max(0, prev - 1));
                }
                toast.success('Notifikasi berhasil dihapus.');
            })
            .catch((err) => console.error('Failed to delete notification:', err));
    };

    const [offlineDraftsCount, setOfflineDraftsCount] = useState(0);
    const [isSyncing, setIsSyncing] = useState(false);

    const checkDrafts = () => {
        try {
            const drafts = JSON.parse(localStorage.getItem('offline_order_drafts') || '[]');
            const queue = JSON.parse(localStorage.getItem('offline_request_queue') || '[]');
            setOfflineDraftsCount(drafts.length + queue.length);
        } catch (e) {
            console.error('Failed to parse offline drafts/requests:', e);
        }
    };

    useEffect(() => {
        checkDrafts();

        window.addEventListener('focus', checkDrafts);
        window.addEventListener('online', checkDrafts);
        window.addEventListener('offline-queue-updated', checkDrafts);
        
        return () => {
            window.removeEventListener('focus', checkDrafts);
            window.removeEventListener('online', checkDrafts);
            window.removeEventListener('offline-queue-updated', checkDrafts);
        };
    }, [isOffline]);

    // Global Inertia interceptor to capture offline submissions for all user roles/pages
    useEffect(() => {
        const unbind = router.on('before', (event) => {
            const method = event.detail.visit.method.toUpperCase();
            if (method === 'GET') return;

            if (!navigator.onLine) {
                // Cancel original network request
                event.preventDefault();

                const url = event.detail.visit.url.toString();
                const data = event.detail.visit.data;

                // Create a clear description of the action being performed
                let actionName = 'Aktivitas';
                if (url.includes('/orders') && method === 'POST') {
                    actionName = `Buat PO "${data?.nama_po || 'Baru'}"`;
                } else if (url.includes('/orders') && method === 'PUT') {
                    actionName = `Edit PO "${data?.nama_po || ''}"`;
                } else if (url.includes('/produksi/progress') && method === 'PUT') {
                    actionName = `Update Progress Tahap`;
                } else if (url.includes('/produksi/rijek') && method === 'POST') {
                    actionName = `Catat Rijek Produksi`;
                } else if (url.includes('/produksi/rijek') && method === 'PUT') {
                    actionName = `Edit Rijek Produksi`;
                } else if (url.includes('/keuangan') || url.includes('/payments') || url.includes('/pembayaran')) {
                    actionName = `Transaksi Keuangan`;
                } else if (url.includes('/notifications')) {
                    return; // Ignore notification status changes offline
                }

                const offlineQueue = JSON.parse(localStorage.getItem('offline_request_queue') || '[]');
                
                // Prevent duplicate requests being queued within 2 seconds
                const isDuplicate = offlineQueue.some(req => 
                    req.url === url && 
                    req.method === method && 
                    JSON.stringify(req.data) === JSON.stringify(data) &&
                    (Date.now() - new Date(req.timestamp).getTime() < 2000)
                );

                if (!isDuplicate) {
                    offlineQueue.push({
                        id: 'req_' + Date.now(),
                        url: url,
                        method: method,
                        data: data,
                        actionName: actionName,
                        timestamp: new Date().toISOString()
                    });
                    localStorage.setItem('offline_request_queue', JSON.stringify(offlineQueue));
                    
                    window.dispatchEvent(new Event('offline-queue-updated'));
                    
                    toast.success(`Aksi "${actionName}" disimpan secara offline!`, {
                        description: 'Akan otomatis disinkronkan saat terhubung ke internet kembali.',
                        duration: 8000
                    });

                    // For the main order form, redirect to order index locally
                    if (url.includes('/orders') && method === 'POST') {
                        router.visit(route('orders.index'));
                    }
                }
            }
        });

        return () => {
            unbind();
        };
    }, []);

    const handleSyncOfflineDrafts = async () => {
        if (isSyncing) return;
        setIsSyncing(true);

        const drafts = JSON.parse(localStorage.getItem('offline_order_drafts') || '[]');
        const queue = JSON.parse(localStorage.getItem('offline_request_queue') || '[]');

        if (drafts.length === 0 && queue.length === 0) {
            setIsSyncing(false);
            return;
        }

        toast.loading('Menyinkronkan data offline ke server...', { id: 'pwa-sync' });

        let successCount = 0;
        const remainingDrafts = [];
        const remainingQueue = [];

        // 1. Sync specific order drafts
        for (const draft of drafts) {
            try {
                const xsrf = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '';
                const config = {
                    headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) }
                };
                
                if (draft.isEdit && draft.orderId) {
                    await axios.put(route('orders.update', draft.orderId), draft.data, config);
                } else {
                    await axios.post(route('orders.store'), draft.data, config);
                }
                successCount++;
            } catch (error) {
                console.error('Failed to sync draft:', error);
                remainingDrafts.push(draft);
                toast.error(`Gagal menyimpan PO "${draft.nama_po}".`, {
                    description: error.response?.data?.message || 'Terjadi kesalahan pada server.'
                });
            }
        }

        // 2. Sync general request queue (for all pages/roles)
        for (const req of queue) {
            try {
                const xsrf = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '';
                const config = {
                    headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) }
                };

                if (req.method === 'POST') {
                    await axios.post(req.url, req.data, config);
                } else if (req.method === 'PUT') {
                    await axios.put(req.url, req.data, config);
                } else if (req.method === 'PATCH') {
                    await axios.patch(req.url, req.data, config);
                } else if (req.method === 'DELETE') {
                    await axios.delete(req.url, { ...config, data: req.data });
                }
                successCount++;
            } catch (error) {
                console.error('Failed to sync queued request:', error);
                remainingQueue.push(req);
                toast.error(`Gagal menyinkronkan "${req.actionName}".`, {
                    description: error.response?.data?.message || 'Terjadi kesalahan pada server.'
                });
            }
        }

        localStorage.setItem('offline_order_drafts', JSON.stringify(remainingDrafts));
        localStorage.setItem('offline_request_queue', JSON.stringify(remainingQueue));
        
        setOfflineDraftsCount(remainingDrafts.length + remainingQueue.length);
        setIsSyncing(false);
        toast.dismiss('pwa-sync');

        if (successCount > 0) {
            toast.success(`Berhasil menyinkronkan ${successCount} aktivitas offline ke server!`, {
                duration: 6000
            });
            router.reload();
        }
    };

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
        if (flash?.info) toast.message(flash.info);
    }, [flash?.success, flash?.error, flash?.info]);

    return (
        <div className="min-h-screen bg-background">
            {app?.favicon_url && (
                <Head>
                    <link rel="icon" href={app.favicon_url} />
                </Head>
            )}
            <Toaster richColors position="top-right" />

            {/* Sidebar desktop */}
            <aside className={cn(
                "fixed inset-y-0 left-0 z-30 hidden border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:block transition-all duration-300",
                isCollapsed ? "w-20" : "w-64"
            )}>
                <SidebarContent 
                    user={user} 
                    brandContext={brandContext} 
                    installPrompt={isInstallable}
                    handleInstall={handleInstallClick}
                    isCollapsed={isCollapsed}
                    app={app}
                />
            </aside>

            {/* Mobile sidebar */}
            <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                <SheetContent side="left" className="w-72 border-r border-sidebar-border bg-sidebar p-0 text-sidebar-foreground">
                    <SheetTitle className="sr-only">Navigasi</SheetTitle>
                    <SheetDescription className="sr-only">Menu navigasi utama</SheetDescription>
                    <SidebarContent 
                        user={user} 
                        brandContext={brandContext} 
                        onNavigate={() => setMobileOpen(false)} 
                        installPrompt={isInstallable}
                        handleInstall={handleInstallClick}
                        isCollapsed={false}
                        app={app}
                    />
                </SheetContent>
            </Sheet>

            <div className={cn("transition-all duration-300", isCollapsed ? "lg:pl-20" : "lg:pl-64")}>
                {/* Top bar */}
                <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur md:px-6">
                    <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setMobileOpen(true)}>
                        <Menu className="h-5 w-5" />
                    </Button>

                    <Button 
                        variant="ghost" 
                        size="icon" 
                        className="hidden lg:flex text-gray-500 hover:text-gray-700 hover:bg-sidebar-accent/20" 
                        onClick={toggleSidebar}
                        title={isCollapsed ? "Expand Sidebar" : "Collapse Sidebar"}
                    >
                        {isCollapsed ? <PanelLeftOpen className="h-5 w-5" /> : <PanelLeftClose className="h-5 w-5" />}
                    </Button>

                    <div className="flex-1 flex items-center gap-3">
                        {header ? (
                            typeof header === 'string' ? (
                                <h1 className="text-base font-semibold sm:text-lg">{header}</h1>
                            ) : (
                                header
                            )
                        ) : title ? (
                            <h1 className="text-base font-semibold sm:text-lg">{title}</h1>
                        ) : null}

                        {isOffline && (
                            <Badge variant="destructive" className="animate-pulse bg-red-600 text-white flex items-center gap-1">
                                <WifiOff className="h-3 w-3" />
                                <span>Offline</span>
                            </Badge>
                        )}
                    </div>

                    <div className="hidden items-center gap-2 sm:flex">
                        {brandContext?.current && (
                            <Badge variant="info" className="hidden md:inline-flex">
                                <span
                                    className="mr-1.5 h-2 w-2 rounded-full"
                                    style={{ background: brandContext.current.warna_primary || '#3B82F6' }}
                                />
                                {brandContext.current.kode}
                            </Badge>
                        )}
                    </div>

                    <div className="flex items-center gap-1.5">
                        {user && (
                            <NotificationDropdown 
                                notifications={notifications}
                                unreadCount={unreadCount}
                                onMarkAsRead={markAsRead}
                                onMarkAllAsRead={markAllAsRead}
                                onDelete={deleteNotification}
                            />
                        )}
                        <UserMenu user={user} />
                    </div>
                </header>

                {!isOffline && offlineDraftsCount > 0 && (
                    <div className="bg-amber-500 text-slate-900 px-4 py-3 flex items-center justify-between text-xs font-bold shadow-md animate-pulse">
                        <div className="flex items-center gap-2">
                            <span className="flex h-2.5 w-2.5 rounded-full bg-red-600 animate-ping" />
                            <span>Terdapat {offlineDraftsCount} draf pesanan offline yang belum disimpan ke server.</span>
                        </div>
                        <Button
                            onClick={handleSyncOfflineDrafts}
                            disabled={isSyncing}
                            size="sm"
                            className="bg-slate-900 text-white hover:bg-slate-800 transition disabled:opacity-50 text-[11px] h-8 font-black uppercase"
                        >
                            {isSyncing ? 'Menyinkronkan...' : 'Sinkronkan Sekarang'}
                        </Button>
                    </div>
                )}

                <main className="px-4 py-6 md:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}

import React, { useState, useEffect, useRef } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import { 
    Bell, 
    Trash2, 
    CheckSquare, 
    Inbox, 
    ExternalLink, 
    Filter, 
    CheckCircle2, 
    Clock, 
    AlertCircle, 
    Sparkles, 
    ShieldAlert, 
    CreditCard, 
    ArrowRightLeft, 
    FileText 
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { toast } from 'sonner';

function formatDateTime(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    } catch (e) {
        return dateString;
    }
}

export default function NotificationsIndex({ notifications, filters, unread_count, total_count, read_count }) {
    const [localNotifications, setLocalNotifications] = useState(notifications.data || []);
    const [localUnreadCount, setLocalUnreadCount] = useState(unread_count);
    const [localTotalCount, setLocalTotalCount] = useState(total_count);
    const [localReadCount, setLocalReadCount] = useState(read_count);
    const currentFilter = filters?.filter || 'all';

    const syncChannel = useRef(null);

    // Multi-tab synchronization channel setup
    useEffect(() => {
        if (typeof window !== 'undefined') {
            syncChannel.current = new window.BroadcastChannel('protrack_notifications');
        }
        return () => {
            syncChannel.current?.close();
        };
    }, []);

    // Sync local notifications state with Inertia prop changes (e.g., when pagination changes)
    useEffect(() => {
        setLocalNotifications(notifications.data || []);
        setLocalUnreadCount(unread_count);
        setLocalTotalCount(total_count);
        setLocalReadCount(read_count);
    }, [notifications.data, unread_count, total_count, read_count]);

    // Handle individual read action
    const handleRead = (id, shouldReload = true) => {
        setLocalNotifications(prev => 
            prev.map(n => n.id === id ? { ...n, is_read: true } : n)
        );
        setLocalUnreadCount(prev => Math.max(0, prev - 1));
        setLocalReadCount(prev => prev + 1);

        axios.post(route('notifications.read', id))
            .then((res) => {
                const serverUnread = res.data.unread_count ?? Math.max(0, localUnreadCount - 1);
                syncChannel.current?.postMessage({ 
                    type: 'NOTIF_READ', 
                    id, 
                    unread_count: serverUnread 
                });
                if (shouldReload) {
                    router.reload({ preserveScroll: true });
                }
            })
            .catch((err) => {
                console.error(err);
                toast.error('Gagal menandai notifikasi dibaca');
            });
    };

    // Handle individual delete action
    const handleDelete = (id) => {
        const target = localNotifications.find(n => n.id === id);
        const wasUnread = target && !target.is_read;

        setLocalNotifications(prev => prev.filter(n => n.id !== id));
        if (wasUnread) {
            setLocalUnreadCount(prev => Math.max(0, prev - 1));
        } else {
            setLocalReadCount(prev => Math.max(0, prev - 1));
        }
        setLocalTotalCount(prev => Math.max(0, prev - 1));

        axios.delete(route('notifications.destroy', id))
            .then((res) => {
                const serverUnread = res.data.unread_count ?? (wasUnread ? Math.max(0, localUnreadCount - 1) : localUnreadCount);
                syncChannel.current?.postMessage({ 
                    type: 'NOTIF_DELETE', 
                    id, 
                    unread_count: serverUnread 
                });
                toast.success('Notifikasi berhasil dihapus');
                router.reload({ preserveScroll: true });
            })
            .catch((err) => {
                console.error(err);
                toast.error('Gagal menghapus notifikasi');
            });
    };

    // Handle mark all read action
    const handleMarkAllRead = () => {
        setLocalNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
        setLocalReadCount(prev => prev + localUnreadCount);
        setLocalUnreadCount(0);

        axios.post(route('notifications.read-all'))
            .then(() => {
                syncChannel.current?.postMessage({ type: 'NOTIF_READ_ALL' });
                toast.success('Semua notifikasi ditandai telah dibaca');
                router.reload({ preserveScroll: true });
            })
            .catch((err) => {
                console.error(err);
                toast.error('Gagal menandai semua dibaca');
            });
    };

    // Handle clear all history action
    const handleClearAll = () => {
        if (confirm('Apakah Anda yakin ingin menghapus seluruh riwayat notifikasi? Tindakan ini tidak dapat dibatalkan.')) {
            setLocalNotifications([]);
            setLocalUnreadCount(0);
            setLocalTotalCount(0);
            setLocalReadCount(0);

            axios.delete(route('notifications.clear-all'))
                .then(() => {
                    syncChannel.current?.postMessage({ type: 'NOTIF_CLEAR_ALL' });
                    toast.success('Seluruh riwayat notifikasi berhasil dihapus');
                    router.reload({ preserveScroll: true });
                })
                .catch((err) => {
                    console.error(err);
                    toast.error('Gagal menghapus riwayat notifikasi');
                });
        }
    };

    // Apply filter via Inertia
    const handleFilterChange = (filterType) => {
        router.get(route('notifications.index'), { filter: filterType }, { preserveState: true, preserveScroll: true });
    };

    // Resolve beautiful details based on notification type
    const getNotificationTypeDetails = (type) => {
        const t = type.toLowerCase();
        if (t.includes('special_order') || t.includes('special')) {
            return {
                icon: <Sparkles className="h-4 w-4" />,
                iconBg: 'bg-purple-50 border-purple-200 text-purple-600 dark:bg-purple-950/30 dark:border-purple-900/50 dark:text-purple-400',
                badgeText: 'Special Order',
                badgeVariant: 'secondary'
            };
        }
        if (t.includes('refund')) {
            return {
                icon: <ArrowRightLeft className="h-4 w-4" />,
                iconBg: 'bg-amber-50 border-amber-200 text-amber-600 dark:bg-amber-950/30 dark:border-amber-900/50 dark:text-amber-400',
                badgeText: 'Refund',
                badgeVariant: 'warning'
            };
        }
        if (t.includes('rijek')) {
            return {
                icon: <ShieldAlert className="h-4 w-4" />,
                iconBg: 'bg-red-50 border-red-200 text-red-600 dark:bg-red-950/30 dark:border-red-900/50 dark:text-red-400',
                badgeText: 'Defect / Rijek',
                badgeVariant: 'destructive'
            };
        }
        if (t.includes('progress') || t.includes('tahapan')) {
            return {
                icon: <Clock className="h-4 w-4" />,
                iconBg: 'bg-blue-50 border-blue-200 text-blue-600 dark:bg-blue-950/30 dark:border-blue-900/50 dark:text-blue-400',
                badgeText: 'Progress Produksi',
                badgeVariant: 'info'
            };
        }
        if (t.includes('payment') || t.includes('pembayaran')) {
            return {
                icon: <CreditCard className="h-4 w-4" />,
                iconBg: 'bg-emerald-50 border-emerald-200 text-emerald-600 dark:bg-emerald-950/30 dark:border-emerald-900/50 dark:text-emerald-400',
                badgeText: 'Pembayaran',
                badgeVariant: 'success'
            };
        }
        if (t.includes('unlock') || t.includes('lock')) {
            return {
                icon: <AlertCircle className="h-4 w-4" />,
                iconBg: 'bg-rose-50 border-rose-200 text-rose-600 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-400',
                badgeText: 'Otorisasi PO',
                badgeVariant: 'destructive'
            };
        }
        return {
            icon: <Bell className="h-4 w-4" />,
            iconBg: 'bg-slate-50 border-slate-200 text-slate-600 dark:bg-slate-950/30 dark:border-slate-900/50 dark:text-slate-400',
            badgeText: 'Sistem',
            badgeVariant: 'default'
        };
    };

    return (
        <AppLayout title="Riwayat Notifikasi">
            <Head title="Riwayat Notifikasi" />

            <div className="space-y-6 max-w-5xl mx-auto">
                {/* Header Section */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight flex items-center gap-2 text-slate-900 dark:text-white">
                            <Bell className="h-6 w-6 text-primary animate-pulse" />
                            Riwayat Notifikasi
                        </h2>
                        <p className="text-sm text-muted-foreground mt-1">
                            Lihat, filter, dan kelola semua riwayat aktivitas, verifikasi pembayaran, dan log status sistem.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {localUnreadCount > 0 && (
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={handleMarkAllRead}
                                className="flex items-center gap-1.5 border-emerald-200 hover:border-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-xs h-9"
                            >
                                <CheckSquare className="h-3.5 w-3.5" />
                                Tandai Semua Dibaca
                            </Button>
                        )}
                        {localTotalCount > 0 && (
                            <Button 
                                variant="destructive" 
                                size="sm" 
                                onClick={handleClearAll}
                                className="flex items-center gap-1.5 text-xs h-9 bg-red-600 hover:bg-red-700"
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                                Hapus Semua Riwayat
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filters and Stats Card */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {/* Stats Blocks */}
                    <Card className="md:col-span-1 border border-slate-100 dark:border-slate-800 shadow-sm bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm">
                        <CardContent className="p-4 flex flex-col justify-center h-full">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Belum Dibaca</span>
                            <div className="flex items-baseline gap-2 mt-1">
                                <span className="text-3xl font-black text-red-600 dark:text-red-500">{localUnreadCount}</span>
                                <span className="text-xs text-muted-foreground">notifikasi</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="md:col-span-1 border border-slate-100 dark:border-slate-800 shadow-sm bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm">
                        <CardContent className="p-4 flex flex-col justify-center h-full">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Sudah Dibaca</span>
                            <div className="flex items-baseline gap-2 mt-1">
                                <span className="text-3xl font-black text-slate-700 dark:text-slate-300">{localReadCount}</span>
                                <span className="text-xs text-muted-foreground">notifikasi</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="md:col-span-1 border border-slate-100 dark:border-slate-800 shadow-sm bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm">
                        <CardContent className="p-4 flex flex-col justify-center h-full">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total Riwayat</span>
                            <div className="flex items-baseline gap-2 mt-1">
                                <span className="text-3xl font-black text-primary">{localTotalCount}</span>
                                <span className="text-xs text-muted-foreground">notifikasi</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Quick Filters */}
                    <Card className="md:col-span-1 border border-slate-100 dark:border-slate-800 shadow-sm bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm flex flex-col justify-center">
                        <CardContent className="p-4 space-y-2">
                            <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider block">Filter Riwayat</span>
                            <div className="flex gap-1.5 flex-wrap">
                                <Button 
                                    size="sm"
                                    variant={currentFilter === 'all' ? 'default' : 'outline'}
                                    onClick={() => handleFilterChange('all')}
                                    className="text-xs px-2.5 h-8"
                                >
                                    Semua
                                </Button>
                                <Button 
                                    size="sm"
                                    variant={currentFilter === 'unread' ? 'default' : 'outline'}
                                    onClick={() => handleFilterChange('unread')}
                                    className={cn(
                                        "text-xs px-2.5 h-8",
                                        currentFilter !== 'unread' && localUnreadCount > 0 && "border-red-200 text-red-600 hover:bg-red-50/50"
                                    )}
                                >
                                    Belum Dibaca {localUnreadCount > 0 && `(${localUnreadCount})`}
                                </Button>
                                <Button 
                                    size="sm"
                                    variant={currentFilter === 'read' ? 'default' : 'outline'}
                                    onClick={() => handleFilterChange('read')}
                                    className="text-xs px-2.5 h-8"
                                >
                                    Sudah Dibaca
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Notifications History List */}
                <Card className="border border-slate-100 dark:border-slate-800 shadow-md overflow-hidden bg-white/80 dark:bg-slate-900/80 backdrop-blur-md">
                    <CardHeader className="border-b bg-slate-50/50 dark:bg-slate-900/50 py-4 px-6 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-sm font-bold text-slate-800 dark:text-slate-200">
                                Daftar Notifikasi {currentFilter === 'unread' ? 'Belum Dibaca' : currentFilter === 'read' ? 'Sudah Dibaca' : 'Semua'}
                            </CardTitle>
                            <CardDescription className="text-xs mt-0.5">
                                Menampilkan urutan kronologis aktivitas sistem Anda.
                            </CardDescription>
                        </div>
                        <Badge variant="outline" className="text-[10px] font-semibold text-muted-foreground">
                            Halaman {notifications.current_page} dari {notifications.last_page}
                        </Badge>
                    </CardHeader>
                    <CardContent className="p-0 divide-y divide-slate-100 dark:divide-slate-800/80">
                        {localNotifications.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
                                <div className="h-16 w-16 rounded-full bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-900 flex items-center justify-center mb-4">
                                    <Inbox className="h-8 w-8 text-muted-foreground/65" />
                                </div>
                                <h3 className="text-sm font-bold text-slate-700 dark:text-slate-300">Tidak ada notifikasi</h3>
                                <p className="text-xs text-muted-foreground max-w-sm mt-1">
                                    {currentFilter === 'unread' 
                                        ? 'Semua notifikasi Anda telah dibaca! Bagus sekali.' 
                                        : 'Daftar riwayat notifikasi kosong. Belum ada aktivitas sistem yang tercatat.'}
                                </p>
                            </div>
                        ) : (
                            localNotifications.map((notif) => {
                                const details = getNotificationTypeDetails(notif.type);
                                return (
                                    <div 
                                        key={notif.id}
                                        className={cn(
                                            "flex flex-col sm:flex-row gap-4 p-5 transition-all duration-200 relative group",
                                            notif.is_read 
                                                ? "bg-transparent hover:bg-slate-50/50 dark:hover:bg-slate-950/10 border-l-4 border-transparent" 
                                                : "bg-red-50/20 hover:bg-red-50/30 dark:bg-red-950/5 dark:hover:bg-red-950/10 border-l-4 border-red-600 dark:border-red-500"
                                        )}
                                    >
                                        {/* Icon Section */}
                                        <div className="flex items-start shrink-0">
                                            <div className={cn(
                                                "h-10 w-10 rounded-xl border flex items-center justify-center shadow-sm transition-transform duration-200 group-hover:scale-105",
                                                details.iconBg
                                            )}>
                                                {details.icon}
                                            </div>
                                        </div>

                                        {/* Text Section */}
                                        <div className="flex-1 space-y-1.5">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h4 className={cn(
                                                    "text-sm font-bold tracking-tight text-slate-900 dark:text-white leading-none",
                                                    !notif.is_read && "font-black"
                                                )}>
                                                    {notif.title}
                                                </h4>
                                                <Badge variant={details.badgeVariant} className="text-[10px] py-0 px-2 font-bold tracking-wide uppercase">
                                                    {details.badgeText}
                                                </Badge>
                                                {notif.no_po && (
                                                    <Badge variant="outline" className="text-[10px] font-mono py-0 font-bold bg-slate-100 border-slate-200 text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                                        PO: {notif.no_po}
                                                    </Badge>
                                                )}
                                                {!notif.is_read && (
                                                    <span className="inline-flex h-2 w-2 rounded-full bg-red-600 animate-pulse" title="Belum Dibaca" />
                                                )}
                                            </div>

                                            <p className={cn(
                                                "text-xs leading-relaxed text-slate-600 dark:text-slate-300",
                                                !notif.is_read && "font-medium"
                                            )}>
                                                {notif.body}
                                            </p>

                                            <div className="flex items-center gap-4 text-[10px] text-muted-foreground font-mono">
                                                <span className="flex items-center gap-1">
                                                    <Clock className="h-3 w-3 shrink-0" />
                                                    {formatDateTime(notif.created_at)}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Actions Section */}
                                        <div className="flex items-center sm:self-center gap-1.5 shrink-0 pt-2 sm:pt-0 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-200">
                                            {notif.action_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 text-xs font-bold flex items-center gap-1 border-slate-200 hover:border-slate-300 bg-white"
                                                    onClick={() => {
                                                        axios.get(route('notifications.verify', notif.id))
                                                            .then((res) => {
                                                                if (res.data.valid) {
                                                                    if (!notif.is_read) {
                                                                        handleRead(notif.id, false);
                                                                    }
                                                                    setTimeout(() => {
                                                                        router.visit(notif.action_url);
                                                                    }, 50);
                                                                } else {
                                                                    if (!notif.is_read) {
                                                                        handleRead(notif.id, true);
                                                                    } else {
                                                                        router.reload({ preserveScroll: true });
                                                                    }
                                                                    toast.warning(res.data.message || 'Resource state invalid or already handled.');
                                                                }
                                                            })
                                                            .catch((err) => {
                                                                console.error('Failed to verify notification state:', err);
                                                                if (!notif.is_read) {
                                                                    handleRead(notif.id, false);
                                                                }
                                                                setTimeout(() => {
                                                                    router.visit(notif.action_url);
                                                                }, 50);
                                                            });
                                                    }}
                                                    title="Lihat Detail Pesanan"
                                                >
                                                    <ExternalLink className="h-3.5 w-3.5" />
                                                    Detail
                                                </Button>
                                            )}
                                            {!notif.is_read && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-8 w-8 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 p-0"
                                                    onClick={() => handleRead(notif.id)}
                                                    title="Tandai telah dibaca"
                                                >
                                                    <CheckCircle2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50 p-0"
                                                onClick={() => handleDelete(notif.id)}
                                                title="Hapus dari riwayat"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </CardContent>
                </Card>

                {/* Pagination Section */}
                {notifications.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-2 text-xs bg-slate-50/50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-xl p-4 shadow-sm">
                        <span className="text-muted-foreground font-semibold">
                            Menampilkan {notifications.from ?? 0}–{notifications.to ?? 0} dari {notifications.total} data
                        </span>
                        <div className="flex gap-1">
                            {notifications.links.map((link, i) => (
                                <Button
                                    key={i}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className="h-8 text-xs font-bold"
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Bell, CheckSquare, Inbox, Trash2, ExternalLink } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { toast } from 'sonner';

function formatTimeAgo(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        if (diffMins < 1) return 'Baru saja';
        if (diffMins < 60) return `${diffMins}m lalu`;
        const diffHours = Math.floor(diffMins / 60);
        if (diffHours < 24) return `${diffHours}j lalu`;
        const diffDays = Math.floor(diffHours / 24);
        return `${diffDays}h lalu`;
    } catch (e) {
        return '';
    }
}

export default function NotificationDropdown({ notifications, unreadCount, onMarkAsRead, onMarkAllAsRead, onDelete, onNavigate, onDropdownOpen }) {
    const [open, setOpen] = useState(false);

    const handleOpenChange = (nextOpen) => {
        setOpen(nextOpen);
        if (nextOpen && onDropdownOpen) {
            onDropdownOpen();
        }
    };

    const handleNotificationClick = (notif) => {
        if (!notif.action_url) {
            onMarkAsRead(notif.id);
            return;
        }

        axios.get(route('notifications.verify', notif.id))
            .then((res) => {
                if (res.data.valid) {
                    onMarkAsRead(notif.id);
                    setOpen(false);
                    onNavigate?.();
                    setTimeout(() => {
                        router.visit(notif.action_url);
                    }, 50);
                } else {
                    onMarkAsRead(notif.id);
                    toast.warning(res.data.message || 'Resource state invalid or already handled.');
                }
            })
            .catch((err) => {
                console.error('Failed to verify notification state:', err);
                onMarkAsRead(notif.id);
                setOpen(false);
                onNavigate?.();
                setTimeout(() => {
                    router.visit(notif.action_url);
                }, 50);
            });
    };

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative h-9 w-9 rounded-lg">
                    <Bell className="h-5 w-5 text-gray-600" />
                    {unreadCount > 0 && (
                        <span className="absolute right-1.5 top-1.5 flex h-2.5 w-2.5">
                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75" />
                            <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500" />
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 sm:w-96 p-0 shadow-lg border rounded-xl overflow-hidden bg-white/95 backdrop-blur-md">
                <div className="flex items-center justify-between border-b px-4 py-3 bg-muted/30">
                    <div>
                        <h3 className="font-semibold text-sm text-gray-800">Notifikasi Sistem</h3>
                        <p className="text-[10px] text-muted-foreground mt-0.5">{unreadCount} belum dibaca</p>
                    </div>
                    {unreadCount > 0 && (
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            className="text-[11px] h-7 text-primary hover:text-primary-hover px-2 flex items-center gap-1"
                            onClick={(e) => {
                                e.stopPropagation();
                                onMarkAllAsRead();
                            }}
                        >
                            <CheckSquare className="h-3 w-3" /> Tandai semua dibaca
                        </Button>
                    )}
                </div>

                <div className="max-h-[350px] overflow-y-auto divide-y divide-gray-100">
                    {notifications.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-8 text-center px-4">
                            <div className="h-10 w-10 rounded-full bg-muted/40 flex items-center justify-center mb-2">
                                <Inbox className="h-5 w-5 text-muted-foreground" />
                            </div>
                            <p className="text-xs font-medium text-gray-500">Tidak ada notifikasi</p>
                            <p className="text-[10px] text-muted-foreground mt-1">Aktivitas sistem Anda akan muncul di sini</p>
                        </div>
                    ) : (
                        notifications.map((notif) => (
                            <div 
                                key={notif.id} 
                                className={cn(
                                    "flex gap-3 p-3 transition-colors cursor-pointer group relative border-b border-gray-100",
                                    notif.is_read 
                                        ? "bg-transparent hover:bg-gray-50 border-l-4 border-transparent" 
                                        : "bg-red-50/60 hover:bg-red-50/80 border-l-4 border-red-600"
                                )}
                                onClick={() => handleNotificationClick(notif)}
                            >
                                <div className={cn(
                                    "mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border shadow-sm",
                                    notif.is_read ? "bg-gray-50 text-gray-400" : "bg-white text-red-600 border-red-100"
                                )}>
                                    <span className="text-sm">
                                        {notif.type?.includes('special_order') ? '⭐' :
                                         notif.type?.includes('refund') ? '🪙' : 
                                         notif.type?.includes('rijek') ? '⚠️' : 
                                         notif.type?.includes('progress') ? '⚙️' : '📦'}
                                    </span>
                                </div>
                                <div className="flex-1 space-y-1 pr-6">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className={cn(
                                            "text-xs line-clamp-1 flex items-center gap-1.5", 
                                            notif.is_read ? "font-medium text-gray-500" : "font-bold text-red-950"
                                        )}>
                                            {!notif.is_read && <span className="h-1.5 w-1.5 rounded-full bg-red-600 shrink-0" />}
                                            {notif.title}
                                        </p>
                                        <span className={cn(
                                            "text-[9px] shrink-0 font-medium font-mono",
                                            notif.is_read ? "text-gray-400" : "text-red-500 font-semibold"
                                        )}>
                                            {formatTimeAgo(notif.created_at)}
                                        </span>
                                    </div>
                                    <p className={cn(
                                        "text-[11px] line-clamp-2 leading-relaxed",
                                        notif.is_read ? "text-gray-400" : "text-gray-700 font-medium"
                                    )}>
                                        {notif.body}
                                    </p>
                                </div>
                                <div className="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center gap-1">
                                    {notif.action_url && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-6 w-6 text-gray-400 hover:text-gray-600"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                handleNotificationClick(notif);
                                            }}
                                            title="Buka Halaman"
                                        >
                                            <ExternalLink className="h-3 w-3" />
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-6 w-6 text-red-400 hover:text-red-600"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onDelete(notif.id);
                                        }}
                                        title="Hapus"
                                    >
                                        <Trash2 className="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
                <div className="border-t p-2 bg-muted/20 text-center">
                    <Button 
                        variant="ghost" 
                        size="sm" 
                        className="w-full text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 flex items-center justify-center gap-1 h-8"
                        onClick={() => {
                            setOpen(false);
                            onNavigate?.();
                            router.visit('/notifications');
                        }}
                    >
                        Lihat Semua Riwayat Notifikasi
                    </Button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

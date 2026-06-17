import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState, useRef } from 'react';
import { Toaster, toast } from 'sonner';
import axios from 'axios';
import {
    LayoutDashboard,
    Building2,
    Users,
    Package,
    Boxes,
    Factory,
    BarChart3,
    Wallet,
    Calendar,
    Bell,
    Wrench,
    Settings,
    LogOut,
    Menu,
    ChevronsUpDown,
    ChevronDown,
    Check,
    User as UserIcon,
    ShieldCheck,
    Shirt,
    Sparkles,
    Move3D,
    Printer,
    PackageOpen,
    Ruler,
    Scissors,
    Landmark,
    ListChecks,
    Layers,
    Tag,
    Target,
    Compass,
    UserCheck,
    Megaphone,
    Trash2,
    ExternalLink,
    LayoutList,
    Volume2,
    BellOff,
    Inbox,
    CheckSquare,
    Download,
    WifiOff,
    PanelLeftClose,
    PanelLeftOpen
} from 'lucide-react';
import { cn, initials, roleLabel } from '@/lib/utils';
import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/Components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { playNotificationSound } from '@/Services/audio';

function hasPermission(user, perm) {
    if (!user) return false;
    if (user.is_superadmin) return true;
    return user.permissions?.includes(perm);
}

const MASTER_ITEMS = [
    { slug: 'bahan-kain', name: 'Bahan Kain', icon: Shirt, group: 'global' },
    { slug: 'logo', name: 'Logo', icon: Sparkles, group: 'global' },
    { slug: 'resleting', name: 'Resleting', icon: Move3D, group: 'global' },
    { slug: 'printing', name: 'Jenis Printing', icon: Printer, group: 'global' },
    { slug: 'paket-order', name: 'Paket Order', icon: PackageOpen, group: 'global' },
    { slug: 'size', name: 'Size / Ukuran', icon: Ruler, group: 'global' },
    { slug: 'pola-jahitan', name: 'Pola Jahitan', icon: Scissors, group: 'global' },
    { slug: 'jenis-setelan',  name: 'Jenis Setelan',  icon: Layers,    group: 'production' },
    { slug: 'pola-produksi',  name: 'Pola Produksi',  icon: Scissors,  group: 'production' },
    { slug: 'jenis-produk',   name: 'Jenis Produk',   icon: Layers,    group: 'production' },
    { slug: 'progress',       name: 'Tahapan Progress', icon: ListChecks, group: 'production' },
    { slug: 'kategori-order', name: 'Kategori Order', icon: Tag, group: 'brand' },
    { slug: 'sumber-order', name: 'Sumber Order', icon: Compass, group: 'brand' },
    { slug: 'jenis-order', name: 'Jenis Order', icon: LayoutList, group: 'brand' },
    { slug: 'iklan', name: 'Promo', icon: Megaphone, group: 'brand' },
    { slug: 'customer-type', name: 'Tipe Pelanggan', icon: UserCheck, group: 'brand' },
    { slug: 'produk', name: 'Produk', icon: Package, group: 'brand' },
    { slug: 'bank', name: 'Bank', icon: Landmark, group: 'brand' },
];

function buildMenu(user) {
    if (!user) return [];

    const sections = [];

    sections.push({
        title: 'Utama',
        items: [{ name: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, active: route().current('dashboard') }],
    });

    const adminItems = [];
    const isAdminReseller = user?.roles?.includes('admin_reseller');
    if (hasPermission(user, 'brand.view') && !isAdminReseller) {
        adminItems.push({ name: 'Brand', href: route('brands.index'), icon: Building2, active: route().current('brands.*') });
    }
    if (isAdminReseller) {
        adminItems.push({ name: 'Brand Reseller', href: route('brands.index'), icon: Building2, active: route().current('brands.*') });
    }
    const isOwnerOrSuper = user?.is_superadmin || user?.roles?.includes('owner');
    if (isOwnerOrSuper || isAdminReseller) {
        adminItems.push({ name: 'Target Penjualan', href: route('brand-targets.index'), icon: Target, active: route().current('brand-targets.*') });
    }
    if (hasPermission(user, 'user.view')) {
        adminItems.push({ name: 'User', href: route('users.index'), icon: Users, active: route().current('users.*') });
    }
    if (user?.is_superadmin) {
        adminItems.push({ name: 'Role & Izin', href: route('roles.index'), icon: ShieldCheck, active: route().current('roles.*') });
    }
    if (adminItems.length) sections.push({ title: 'Administrasi', items: adminItems });

    const canManageAll        = hasPermission(user, 'master.manage');
    const canManageBrand      = canManageAll || hasPermission(user, 'master.brand');
    const canManageProduk     = hasPermission(user, 'master.produk');
    const canManageProduction = hasPermission(user, 'master.production');

    if (canManageBrand || canManageProduk || canManageProduction) {
        const isMasterActive = route().current('master.*');
        const customerActive = route().current('master.pelanggan.*');
        const currentSlug = route().params?.slug;

        const visibleItems = MASTER_ITEMS.filter((m) => {
            if (canManageAll) return true;
            if (canManageBrand && m.group === 'brand') return true;
            if (canManageProduk && m.slug === 'produk') return true;
            // admin_produksi: lihat semua master produksi (global = bahan, size, logo, pola, dll) + tahapan progress
            if (canManageProduction && (
                m.group === 'production' || 
                m.group === 'global'
            )) return true;
            return false;
        });

        const masterChildren = [
            ...visibleItems.map((m) => ({
                name: m.name,
                href: route('master.index', m.slug),
                icon: m.icon,
                active: route().current('master.index') && currentSlug === m.slug,
            })),
            // Pelanggan hanya tampil jika bukan role produksi murni
            ...(canManageBrand || canManageProduk ? [{
                name: 'Pelanggan',
                href: route('master.pelanggan.index'),
                icon: Users,
                active: customerActive,
            }] : []),
        ];

        sections.push({
            title: 'Master Data',
            items: [
                {
                    name: 'Master Data',
                    icon: Boxes,
                    children: masterChildren,
                    defaultOpen: isMasterActive,
                },
            ],
        });
    }

    const opsItems = [];
    if (hasPermission(user, 'order.view')) {
        opsItems.push({
            name: 'Order',
            href: route('orders.index'),
            icon: Package,
            active: route().current('orders.*'),
        });
    }
    if (hasPermission(user, 'production.update-progress') || hasPermission(user, 'order.view')) {
        opsItems.push({
            name: 'Produksi',
            icon: Factory,
            defaultOpen: route().current('produksi.*'),
            active: route().current('produksi.*'),
            children: [
                {
                    name: 'Kanban',
                    href: route('produksi.kanban'),
                    icon: Factory,
                    active: route().current('produksi.kanban'),
                },
                {
                    name: 'Gantt Chart',
                    href: route('produksi.gantt'),
                    icon: Factory,
                    active: route().current('produksi.gantt'),
                },
            ],
        });
    }
    if (hasPermission(user, 'finance.view')) {
        opsItems.push({
            name: 'Keuangan & Invoice',
            icon: Wallet,
            defaultOpen: route().current('invoices.*'),
            active: route().current('invoices.*'),
            children: [
                {
                    name: 'Ringkasan Keuangan',
                    href: route('invoices.index'),
                    icon: BarChart3,
                    active: route().current('invoices.index'),
                },
                {
                    name: 'List Invoice',
                    href: route('invoices.list'),
                    icon: Wallet,
                    active: route().current('invoices.list') || route().current('invoices.pdf') || route().current('invoices.validate') || route().current('invoices.publish'),
                },
                {
                    name: 'Validasi Pembayaran',
                    href: route('invoices.payments.pending'),
                    icon: ShieldCheck,
                    active: route().current('invoices.payments.pending') || route().current('invoices.payments.verify'),
                },
                {
                    name: 'Master Pembayaran',
                    href: route('master-pembayaran.index'),
                    icon: Boxes,
                    active: route().current('master-pembayaran.*'),
                }
            ]
        });
    } else if (hasPermission(user, 'finance.manage-invoice')) {
        opsItems.push({
            name: 'Invoice',
            href: route('invoices.list'),
            icon: Wallet,
            active: route().current('invoices.list') || route().current('invoices.pdf') || route().current('invoices.validate') || route().current('invoices.publish'),
        });
    }
    if (hasPermission(user, 'order.refund') || hasPermission(user, 'finance.manage-refund')) {
        opsItems.push({
            name: 'Refund',
            href: route('refunds.index'),
            icon: Wallet,
            active: route().current('refunds.*'),
        });
    }
    if (hasPermission(user, 'order.view')) {
        opsItems.push({ name: 'Kalender PO', href: route('kalender.index'), icon: Calendar, active: route().current('kalender.*') });
    }
    if (opsItems.length) sections.push({ title: 'Operasional', items: opsItems });

    const analyticsItems = [];
    if (hasPermission(user, 'report.view')) {
        const REPORT_ITEMS = [
            // Marketing & Pasar
            { slug: 'analisis-marketing', name: 'Analisis Pasar' },
            { slug: 'penjualan-produk',   name: 'Penjualan & Produk' },
            { slug: 'pelanggan',          name: 'Pelanggan' },
            { slug: 'crm-churn',          name: 'Prediksi Churn & CRM' },
            { slug: 'crm-seasonal',       name: 'Pengingat Order Musiman' },
            { slug: 'wilayah',            name: 'Wilayah' },
            { slug: 'kategori',           name: 'Kategori Order' },
            // Operasional
            { slug: 'status-po',          name: 'Status PO' },
            { slug: 'monitoring-deadline', name: 'Monitoring Deadline' },
            { slug: 'rijek',              name: 'Rijek Produksi' },
            { slug: 'refund',             name: 'Refund' },
            // Keuangan
            { slug: 'pemasukan',          name: 'Pemasukan' },
            { slug: 'pengeluaran',        name: 'Pengeluaran' },
            { slug: 'arus-kas-bank',      name: 'Arus Kas & Rekonsiliasi Bank' },
        ];
        const currentSlug = route().params?.slug;
        const isReportActive = route().current('reports.*');
        const isComparisonActive = route().current('comparison.*');
        analyticsItems.push({
            name: 'Laporan',
            icon: BarChart3,
            defaultOpen: isReportActive || isComparisonActive,
            children: [
                ...REPORT_ITEMS.map((r) => ({
                    name: r.name,
                    href: route('reports.show', r.slug),
                    icon: null,
                    active: isReportActive && currentSlug === r.slug,
                })),
                {
                    name: 'Comparison (Multi-Brand)',
                    href: route('comparison.show'),
                    icon: null,
                    active: isComparisonActive,
                },
            ],
        });
    }
    if (hasPermission(user, 'tools.ai')) {
        analyticsItems.push({
            name: 'AI Tools',
            href: route('tools.ai.index'),
            icon: Wrench,
            active: route().current('tools.ai.*'),
        });
    }
    if (analyticsItems.length) sections.push({ title: 'Analitik & Tools', items: analyticsItems });

    const settingItems = [];
    if (user.is_superadmin || hasPermission(user, 'settings.system')) {
        settingItems.push({
            name: 'Integrasi (AI/WA/TG)',
            href: route('settings.integrasi'),
            icon: Settings,
            active: route().current('settings.integrasi*'),
        });
    }
    if (hasPermission(user, 'audit.view')) {
        settingItems.push({
            name: 'Audit Log',
            href: route('audit.index'),
            icon: ShieldCheck,
            active: route().current('audit.*'),
        });
    }
    if (user.is_superadmin || hasPermission(user, 'settings.system')) {
        settingItems.push({
            name: 'Notifikasi',
            href: route('settings.notifikasi'),
            icon: Bell,
            active: route().current('settings.notifikasi*'),
        });
    }
    sections.push({ title: 'Pengaturan', items: settingItems });

    return sections;
}

function NavItem({ item, onNavigate, isCollapsed }) {
    const Icon = item.icon;
    const [open, setOpen] = useState(item.defaultOpen ?? false);
    const itemRef = useRef(null);

    useEffect(() => {
        if (item.active && itemRef.current) {
            const timer = setTimeout(() => {
                itemRef.current.scrollIntoView({ block: 'nearest', behavior: 'auto' });
            }, 100);
            return () => clearTimeout(timer);
        }
    }, [item.active]);

    if (isCollapsed) {
        if (item.children?.length) {
            const childActive = item.children.some((c) => c.active);
            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            title={item.name}
                            className={cn(
                                'flex w-full items-center justify-center rounded-lg p-2 text-sm font-medium transition-colors outline-none',
                                childActive
                                    ? 'bg-sidebar-accent/30 text-sidebar-foreground'
                                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/40 hover:text-sidebar-foreground',
                            )}
                        >
                            <Icon className="h-5 w-5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent side="right" align="start" className="w-56 ml-2 bg-sidebar text-sidebar-foreground border-sidebar-border shadow-lg">
                        <DropdownMenuLabel className="text-xs text-sidebar-foreground/50 uppercase tracking-wider">{item.name}</DropdownMenuLabel>
                        <DropdownMenuSeparator className="bg-sidebar-border" />
                        {item.children.map((c) => (
                            <DropdownMenuItem key={c.name} asChild className="focus:bg-sidebar-accent focus:text-sidebar-foreground cursor-pointer">
                                <Link href={c.href} onClick={() => onNavigate?.()} className={cn("flex items-center gap-2 w-full px-2 py-1.5 rounded-md text-sm", c.active && "bg-sidebar-accent text-sidebar-foreground font-semibold")}>
                                    {c.icon && <c.icon className="h-4 w-4 shrink-0" />}
                                    <span>{c.name}</span>
                                </Link>
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        }

        const content = (
            <span
                title={item.name}
                className={cn(
                    'flex items-center justify-center rounded-lg p-2 transition-colors',
                    item.active
                        ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                        : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/40 hover:text-sidebar-foreground',
                    item.soon && 'cursor-not-allowed opacity-60 hover:bg-transparent hover:text-sidebar-foreground/70',
                )}
            >
                {Icon && <Icon className="h-5 w-5" />}
            </span>
        );

        if (item.soon) return <div>{content}</div>;

        return (
            <Link ref={itemRef} href={item.href} onClick={() => onNavigate?.()} className="block">
                {content}
            </Link>
        );
    }

    if (item.children?.length) {
        const childActive = item.children.some((c) => c.active);
        const isOpen = open || childActive;
        return (
            <div>
                <button
                    type="button"
                    onClick={() => setOpen((v) => !v)}
                    className={cn(
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        childActive
                            ? 'bg-sidebar-accent/30 text-sidebar-foreground'
                            : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/40 hover:text-sidebar-foreground',
                    )}
                >
                    <Icon className="h-4 w-4" />
                    <span className="flex-1 text-left">{item.name}</span>
                    <ChevronDown className={cn('h-4 w-4 transition-transform', isOpen ? 'rotate-0' : '-rotate-90')} />
                </button>
                {isOpen && (
                    <div className="ml-3 mt-1 space-y-0.5 border-l border-sidebar-border pl-3">
                        {item.children.map((c) => (
                            <NavItem key={c.name} item={c} onNavigate={onNavigate} isCollapsed={false} />
                        ))}
                    </div>
                )}
            </div>
        );
    }

    const content = (
        <span
            className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                item.active
                    ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                    : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/40 hover:text-sidebar-foreground',
                item.soon && 'cursor-not-allowed opacity-60 hover:bg-transparent hover:text-sidebar-foreground/70',
            )}
        >
            {Icon && <Icon className="h-4 w-4" />}
            <span className="flex-1">{item.name}</span>
            {item.soon && <Badge variant="outline" className="border-sidebar-foreground/20 text-[10px] text-sidebar-foreground/60">Soon</Badge>}
        </span>
    );

    if (item.soon) return <div>{content}</div>;

    return (
        <Link ref={itemRef} href={item.href} onClick={() => onNavigate?.()} className="block">
            {content}
        </Link>
    );
}

function SidebarContent({ user, brandContext, onNavigate, installPrompt, handleInstall, isCollapsed, app }) {
    const sections = buildMenu(user);
    const navRef = useRef(null);

    useEffect(() => {
        const savedScroll = sessionStorage.getItem('sidebar-scroll-position');
        if (savedScroll && navRef.current) {
            navRef.current.scrollTop = parseInt(savedScroll, 10);
        }
    }, []);

    const handleScroll = (e) => {
        sessionStorage.setItem('sidebar-scroll-position', e.currentTarget.scrollTop);
    };

    return (
        <div className="flex h-full flex-col">
            <div className={cn("flex h-16 shrink-0 items-center border-b border-sidebar-border transition-all duration-300", isCollapsed ? "justify-center px-0" : "gap-2 px-5")}>
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-sidebar-accent text-sidebar-accent-foreground">
                    <ShieldCheck className="h-5 w-5" />
                </div>
                {!isCollapsed && (
                    <div className="leading-tight">
                        <div className="text-sm font-semibold text-sidebar-foreground">{app?.name ?? 'NISReport'}</div>
                        <div className="text-[11px] text-sidebar-foreground/60">{app?.description ?? 'Multi-Brand Order Mgmt'}</div>
                    </div>
                )}
            </div>

            {!user?.roles?.includes('admin_produksi') && !user?.roles?.includes('admin_keuangan') && (
                <BrandSwitcher brandContext={brandContext} userRoles={user?.roles} isCollapsed={isCollapsed} />
            )}

            <nav 
                ref={navRef}
                onScroll={handleScroll}
                className="flex-1 space-y-6 overflow-y-auto px-3 py-4 scrollbar-thin"
            >
                {sections.map((section, idx) => (
                    <div key={section.title}>
                        {!isCollapsed ? (
                            <div className="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/40">
                                {section.title}
                            </div>
                        ) : (
                            idx > 0 && <div className="h-px bg-sidebar-border/50 my-4 mx-2" />
                        )}
                        <div className="space-y-1">
                            {section.items.map((item) => (
                                <NavItem key={item.name} item={item} onNavigate={onNavigate} isCollapsed={isCollapsed} />
                            ))}
                        </div>
                    </div>
                ))}
            </nav>

            <div className={cn("border-t border-sidebar-border p-3 space-y-2", isCollapsed && "p-0")}>
                {isCollapsed ? (
                    <div className="flex flex-col items-center gap-2 border-t border-sidebar-border p-3">
                        {installPrompt && (
                            <Button 
                                onClick={handleInstall}
                                variant="outline" 
                                size="sm" 
                                title="Instal Aplikasi PWA"
                                className="h-9 w-9 p-0 flex items-center justify-center bg-blue-500/10 border-blue-500/20 text-blue-600 hover:bg-blue-600 hover:text-white"
                            >
                                <Download className="h-4 w-4" />
                            </Button>
                        )}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button type="button" className="outline-none">
                                    <Avatar className="h-9 w-9 ring-2 ring-sidebar-border">
                                        <AvatarFallback>{initials(user?.name)}</AvatarFallback>
                                    </Avatar>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="center" className="w-56 ml-2 bg-sidebar text-sidebar-foreground border-sidebar-border shadow-lg">
                                <DropdownMenuLabel>
                                    <div className="font-semibold text-sidebar-foreground">{user?.name}</div>
                                    <div className="text-[10px] text-sidebar-foreground/60 truncate">{user?.email}</div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator className="bg-sidebar-border" />
                                <DropdownMenuItem asChild className="focus:bg-sidebar-accent focus:text-sidebar-foreground">
                                    <Link href={route('profile.edit')} className="cursor-pointer">
                                        <UserIcon className="mr-2 h-4 w-4" /> Profil
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator className="bg-sidebar-border" />
                                <DropdownMenuItem asChild className="text-destructive focus:text-destructive focus:bg-destructive/10">
                                    <Link href={route('logout')} method="post" as="button" className="w-full cursor-pointer text-left">
                                        <LogOut className="mr-2 h-4 w-4" /> Keluar
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                ) : (
                    <>
                        {installPrompt && (
                            <Button 
                                onClick={handleInstall}
                                variant="outline" 
                                size="sm" 
                                className="w-full text-xs flex items-center justify-center gap-1.5 bg-blue-500/10 border-blue-500/20 text-blue-600 hover:bg-blue-600 hover:text-white"
                            >
                                <Download className="h-3.5 w-3.5" /> Instal Aplikasi PWA
                            </Button>
                        )}
                        <div className="rounded-lg bg-sidebar-accent/10 p-3 text-xs text-sidebar-foreground/70">
                            <div className="font-semibold text-sidebar-foreground">{user?.name}</div>
                            <div className="truncate">{user?.email}</div>
                            <div className="mt-2 flex flex-wrap gap-1">
                                {user?.roles?.map((r) => (
                                    <Badge key={r} variant="outline" className="border-sidebar-foreground/30 bg-transparent text-[10px] text-sidebar-foreground/80">
                                        {roleLabel(r)}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

function BrandSwitcher({ brandContext, userRoles = [], isCollapsed }) {
    const current = brandContext?.current;
    const available = brandContext?.available ?? [];
    const isAdminReseller = userRoles.includes('admin_reseller');

    if (!current) return null;

    function switchBrand(id) {
        if (id === current.id) return;
        router.post(route('brand.switch', id), {}, { preserveScroll: true });
    }

    const switcherLabel = isAdminReseller ? 'Brand Reseller Aktif' : 'Brand Aktif';

    if (isCollapsed) {
        return (
            <div className="flex justify-center py-3">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            title={`${switcherLabel}: ${current.nama_brand}`}
                            className="flex h-9 w-9 items-center justify-center rounded-lg border border-sidebar-border bg-sidebar-accent/10 transition hover:bg-sidebar-accent/20 focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                        >
                            <span
                                className="h-4 w-4 shrink-0 rounded-full ring-1 ring-sidebar-border"
                                style={{ background: current.warna_primary || '#3B82F6' }}
                            />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="center" className="w-64 ml-2 bg-sidebar text-sidebar-foreground border-sidebar-border shadow-lg">
                        <DropdownMenuLabel className="text-xs text-sidebar-foreground/50 uppercase tracking-wider">{isAdminReseller ? 'Pilih Brand Reseller' : 'Pilih Brand'}</DropdownMenuLabel>
                        <DropdownMenuSeparator className="bg-sidebar-border" />
                        {available.map((b) => (
                            <DropdownMenuItem key={b.id} onSelect={() => switchBrand(b.id)} className="focus:bg-sidebar-accent focus:text-sidebar-foreground cursor-pointer">
                                <span className="flex flex-1 items-center gap-2">
                                    <span className="h-3 w-3 rounded-full" style={{ background: b.warna_primary || '#3B82F6' }} />
                                    <span className="truncate">{b.nama_brand}</span>
                                    {!b.is_active && <Badge variant="outline" className="text-[10px]">Non-Aktif</Badge>}
                                </span>
                                {b.id === current.id && <Check className="h-4 w-4 text-primary" />}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        );
    }

    return (
        <div className="px-3 py-3">
            <div className="mb-2 px-2 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/40">{switcherLabel}</div>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        type="button"
                        className="flex w-full items-center justify-between gap-2 rounded-lg border border-sidebar-border bg-sidebar-accent/10 px-3 py-2 text-left text-sm font-medium text-sidebar-foreground transition hover:bg-sidebar-accent/20 focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                    >
                        <span className="flex items-center gap-2">
                            <span
                                className="h-3 w-3 shrink-0 rounded-full ring-1 ring-sidebar-border"
                                style={{ background: current.warna_primary || '#3B82F6' }}
                            />
                            <span className="truncate">{current.nama_brand}</span>
                        </span>
                        <ChevronsUpDown className="h-4 w-4 text-sidebar-foreground/50" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="w-64">
                    <DropdownMenuLabel>{isAdminReseller ? 'Pilih Brand Reseller' : 'Pilih Brand'}</DropdownMenuLabel>
                    {available.map((b) => (
                        <DropdownMenuItem key={b.id} onSelect={() => switchBrand(b.id)} className="cursor-pointer">
                            <span className="flex flex-1 items-center gap-2">
                                <span className="h-3 w-3 rounded-full" style={{ background: b.warna_primary || '#3B82F6' }} />
                                <span className="truncate">{b.nama_brand}</span>
                                {!b.is_active && <Badge variant="outline" className="text-[10px]">Non-Aktif</Badge>}
                            </span>
                            {b.id === current.id && <Check className="h-4 w-4 text-primary" />}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}

function UserMenu({ user }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="gap-2 px-2">
                    <Avatar className="h-8 w-8">
                        <AvatarFallback>{initials(user?.name)}</AvatarFallback>
                    </Avatar>
                    <span className="hidden text-left sm:block">
                        <span className="block text-sm font-medium leading-none">{user?.name}</span>
                        <span className="block text-xs text-muted-foreground">{roleLabel(user?.roles?.[0])}</span>
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>{user?.email}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={route('profile.edit')} className="cursor-pointer">
                        <UserIcon className="mr-2 h-4 w-4" /> Profil
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild className="text-destructive focus:text-destructive">
                    <Link href={route('logout')} method="post" as="button" className="w-full cursor-pointer">
                        <LogOut className="mr-2 h-4 w-4" /> Keluar
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

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

function NotificationDropdown({ notifications, unreadCount, onMarkAsRead, onMarkAllAsRead, onDelete, onNavigate }) {
    const [open, setOpen] = useState(false);

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
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
                                    "flex gap-3 p-3 transition-colors hover:bg-muted/30 cursor-pointer group relative",
                                    !notif.is_read && "bg-blue-50/40 border-l-2 border-blue-500"
                                )}
                                onClick={() => {
                                    onMarkAsRead(notif.id);
                                    if (notif.action_url) {
                                        setOpen(false);
                                        onNavigate?.();
                                        router.visit(notif.action_url);
                                    }
                                }}
                            >
                                <div className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white border shadow-sm">
                                    <span className="text-sm">
                                        {notif.type?.includes('refund') ? '🪙' : 
                                         notif.type?.includes('rijek') ? '⚠️' : 
                                         notif.type?.includes('progress') ? '⚙️' : '📦'}
                                    </span>
                                </div>
                                <div className="flex-1 space-y-1 pr-6">
                                    <div className="flex items-center justify-between gap-1">
                                        <p className={cn("text-xs font-semibold text-gray-800 line-clamp-1", !notif.is_read && "text-blue-900")}>
                                            {notif.title}
                                        </p>
                                        <span className="text-[9px] text-muted-foreground shrink-0 font-medium font-mono">
                                            {formatTimeAgo(notif.created_at)}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-gray-600 line-clamp-2 leading-relaxed">
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
                                                onMarkAsRead(notif.id);
                                                onNavigate?.();
                                                router.visit(notif.action_url);
                                                setOpen(false);
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
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

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

    // Core function to execute dynamic sound and OS Native alert
    const handleNewNotification = (notif) => {
        if (notif.sound) {
            playNotificationSound(notif.sound);
        }

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notif.title, {
                body: notif.body,
                icon: '/favicon.ico'
            });
        }

        setNotifications((prev) => {
            if (prev.some(existing => existing.id === notif.id)) return prev;
            return [notif, ...prev.slice(0, 9)];
        });
        setUnreadCount((prev) => prev + 1);

        toast.info(notif.title, {
            description: notif.body,
            action: notif.action_url ? {
                label: 'Lihat',
                onClick: () => router.visit(notif.action_url)
            } : null
        });
    };

    // WebSocket broadcaster listener + Polling Sync
    useEffect(() => {
        if (!user) return;

        // Sync initial state if it changes in Inertia
        setNotifications(user.recent_notifications || []);
        setUnreadCount(user.unread_notifications_count || 0);

        let channel = null;
        if (window.Echo) {
            channel = window.Echo.private(`notifications.user.${user.id}`)
                .listen('.notification.sent', (e) => {
                    if (e.notification) {
                        handleNewNotification(e.notification);
                    }
                });
        }

        const interval = setInterval(() => {
            axios.get(route('notifications.index'))
                .then((res) => {
                    const latest = res.data.notifications?.data || [];
                    const serverUnread = res.data.notifications?.total - latest.filter(n => n.is_read).length || 0;
                    
                    if (latest.length > 0 && latest[0].id !== notifications[0]?.id) {
                        const newNotifs = latest.filter(n => !notifications.some(existing => existing.id === n.id));
                        if (newNotifs.length > 0) {
                            newNotifs.reverse().forEach((n) => {
                                handleNewNotification(n);
                            });
                        }
                    } else {
                        setNotifications(latest.slice(0, 10));
                        setUnreadCount(serverUnread);
                    }
                })
                .catch((err) => console.debug('Polling notifications skipped or offline:', err));
        }, 30000);

        return () => {
            if (channel && window.Echo) {
                window.Echo.leave(`notifications.user.${user.id}`);
            }
            clearInterval(interval);
        };
    }, [user, notifications]);

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

                {/* Offline Sync Banner */}
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

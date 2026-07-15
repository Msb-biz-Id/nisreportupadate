import { useEffect, useRef } from 'react';
import { Link, usePage } from '@inertiajs/react';
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
    LayoutList,
    Download,
    AlertTriangle,
} from 'lucide-react';
import { cn, initials, roleLabel } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import BrandSwitcher from './BrandSwitcher';
import NavItem from './NavItem';

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
    { slug: 'sumber-order', name: 'Sumber Order', icon: Compass, group: 'brand' },
    { slug: 'jenis-order', name: 'Jenis Order', icon: LayoutList, group: 'brand' },
    { slug: 'iklan', name: 'Promo', icon: Megaphone, group: 'brand' },
    { slug: 'customer-type', name: 'Tipe Pelanggan', icon: UserCheck, group: 'brand' },
    { slug: 'produk', name: 'Produk', icon: Package, group: 'brand' },
    { slug: 'bank', name: 'Bank', icon: Landmark, group: 'brand' },
    { slug: 'jenis-masalah', name: 'Jenis Masalah', icon: AlertTriangle, group: 'finance' },
];

function buildMenu(user, reportsList = []) {
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
    const canManageFinance    = hasPermission(user, 'finance.manage-refund') || user?.roles?.includes('admin_keuangan');

    if (canManageBrand || canManageProduk || canManageProduction || canManageFinance) {
        const isMasterActive = route().current('master.*');
        const customerActive = route().current('master.pelanggan.*');
        const currentSlug = route().params?.slug;

        const visibleItems = MASTER_ITEMS.filter((m) => {
            if (canManageAll) return true;
            if (canManageBrand && m.group === 'brand') return true;
            if (canManageProduk && m.slug === 'produk') return true;
            if (canManageProduction && (
                m.group === 'production' || 
                m.group === 'global'
            )) return true;
            if (canManageFinance && m.group === 'finance') return true;
            return false;
        });

        const masterChildren = [
            ...visibleItems.map((m) => ({
                name: m.name,
                href: route('master.index', m.slug),
                icon: m.icon,
                active: route().current('master.index') && currentSlug === m.slug,
            })),
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
        const currentSlug = route().params?.slug;
        const isReportActive = route().current('reports.*');
        const isComparisonActive = route().current('comparison.*');
        const allowedReports = user?.allowed_reports || [];
        const filteredReportItems = reportsList.filter((r) => allowedReports.includes(r.slug) && r.slug !== 'comparison');
        const showComparison = allowedReports.includes('comparison');

        const reportChildren = [
            ...filteredReportItems.map((r) => ({
                name: r.name,
                href: route('reports.show', r.slug),
                icon: null,
                active: isReportActive && currentSlug === r.slug,
            })),
            ...(showComparison ? [{
                name: 'Comparison (Multi-Brand)',
                href: route('comparison.show'),
                icon: null,
                active: isComparisonActive,
            }] : []),
        ];

        if (reportChildren.length > 0) {
            analyticsItems.push({
                name: 'Laporan',
                icon: BarChart3,
                defaultOpen: isReportActive || isComparisonActive,
                children: reportChildren,
            });
        }
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
        settingItems.push({
            name: 'Backup Media',
            href: route('settings.backup'),
            icon: Download,
            active: route().current('settings.backup*'),
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

export default function SidebarContent({ user, brandContext, onNavigate, installPrompt, handleInstall, isCollapsed, app }) {
    const { reports_list = [] } = usePage().props;
    const sections = buildMenu(user, reports_list);
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
                {app?.logo_url ? (
                    <img 
                        src={app.logo_url} 
                        alt={app?.name ?? 'ProTrack'} 
                        className="h-9 w-9 rounded-lg object-contain bg-white p-1 border border-sidebar-border shadow-sm shrink-0" 
                    />
                ) : (
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-sidebar-accent text-sidebar-accent-foreground shrink-0">
                        <ShieldCheck className="h-5 w-5" />
                    </div>
                )}
                {!isCollapsed && (
                    <div className="leading-tight">
                        <div className="text-sm font-semibold text-sidebar-foreground">{app?.name ?? 'ProTrack'}</div>
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
                                className="h-9 w-9 p-0 flex items-center justify-center bg-red-500/10 border-red-500/20 text-red-600 hover:bg-red-600 hover:text-white"
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
                                className="w-full text-xs flex items-center justify-center gap-1.5 bg-red-500/10 border-red-500/20 text-red-600 hover:bg-red-600 hover:text-white"
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

import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Toaster, toast } from 'sonner';
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
    Tag,
    Compass,
    UserCheck,
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
    { slug: 'tipe-order', name: 'Tipe Order', icon: Boxes, group: 'global' },
    { slug: 'size', name: 'Size / Ukuran', icon: Ruler, group: 'global' },
    { slug: 'pola-jahitan', name: 'Pola Jahitan', icon: Scissors, group: 'global' },
    { slug: 'progress', name: 'Tahapan Progress', icon: ListChecks, group: 'global' },
    { slug: 'kategori-order', name: 'Kategori Order', icon: Tag, group: 'brand' },
    { slug: 'sumber-order', name: 'Sumber Order', icon: Compass, group: 'brand' },
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
    if (hasPermission(user, 'brand.view')) {
        adminItems.push({ name: 'Brand', href: route('brands.index'), icon: Building2, active: route().current('brands.*') });
    }
    if (hasPermission(user, 'user.view')) {
        adminItems.push({ name: 'User', href: route('users.index'), icon: Users, active: route().current('users.*') });
    }
    if (adminItems.length) sections.push({ title: 'Administrasi', items: adminItems });

    if (hasPermission(user, 'master.manage')) {
        const isMasterActive = route().current('master.*');
        const customerActive = route().current('master.pelanggan.*');
        const currentSlug = route().params?.slug;

        const masterChildren = [
            ...MASTER_ITEMS.map((m) => ({
                name: m.name,
                href: route('master.index', m.slug),
                icon: m.icon,
                active: route().current('master.index') && currentSlug === m.slug,
            })),
            {
                name: 'Pelanggan',
                href: route('master.pelanggan.index'),
                icon: Users,
                active: customerActive,
            },
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
    if (hasPermission(user, 'finance.view') || hasPermission(user, 'finance.manage-invoice')) {
        opsItems.push({
            name: 'Invoice',
            href: route('invoices.index'),
            icon: Wallet,
            active: route().current('invoices.*'),
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
            { slug: 'penjualan-produk', name: 'Penjualan & Produk' },
            { slug: 'pelanggan', name: 'Pelanggan' },
            { slug: 'wilayah', name: 'Wilayah' },
            { slug: 'kategori', name: 'Kategori Order' },
            { slug: 'status-po', name: 'Status PO' },
            { slug: 'monitoring-deadline', name: 'Monitoring Deadline' },
            { slug: 'rijek', name: 'Rijek Produksi' },
            { slug: 'refund', name: 'Refund' },
            { slug: 'pemasukan', name: 'Pemasukan' },
            { slug: 'pengeluaran', name: 'Pengeluaran' },
            { slug: 'laba-rugi', name: 'Laba Rugi' },
            { slug: 'peak-hours', name: 'Peak Hours' },
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
                    icon: BarChart3,
                    active: isReportActive && currentSlug === r.slug,
                })),
                {
                    name: 'Comparison (Multi-Brand)',
                    href: route('comparison.show'),
                    icon: BarChart3,
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
    settingItems.push({ name: 'Notifikasi', href: '#', icon: Bell, soon: true });
    sections.push({ title: 'Pengaturan', items: settingItems });

    return sections;
}

function NavItem({ item, onNavigate }) {
    const Icon = item.icon;
    const [open, setOpen] = useState(item.defaultOpen ?? false);

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
                            <NavItem key={c.name} item={c} onNavigate={onNavigate} />
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
            <Icon className="h-4 w-4" />
            <span className="flex-1">{item.name}</span>
            {item.soon && <Badge variant="outline" className="border-sidebar-foreground/20 text-[10px] text-sidebar-foreground/60">Soon</Badge>}
        </span>
    );

    if (item.soon) return <div>{content}</div>;

    return (
        <Link href={item.href} onClick={() => onNavigate?.()} className="block">
            {content}
        </Link>
    );
}

function SidebarContent({ user, brandContext, onNavigate }) {
    const sections = buildMenu(user);
    const current = brandContext?.current;

    return (
        <div className="flex h-full flex-col">
            <div className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border px-5">
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-sidebar-accent text-sidebar-accent-foreground">
                    <ShieldCheck className="h-5 w-5" />
                </div>
                <div className="leading-tight">
                    <div className="text-sm font-semibold text-sidebar-foreground">NISReport</div>
                    <div className="text-[11px] text-sidebar-foreground/60">Multi-Brand Order Mgmt</div>
                </div>
            </div>

            <BrandSwitcher brandContext={brandContext} />

            <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4 scrollbar-thin">
                {sections.map((section) => (
                    <div key={section.title}>
                        <div className="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/40">
                            {section.title}
                        </div>
                        <div className="space-y-1">
                            {section.items.map((item) => (
                                <NavItem key={item.name} item={item} onNavigate={onNavigate} />
                            ))}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="border-t border-sidebar-border p-3">
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
            </div>
        </div>
    );
}

function BrandSwitcher({ brandContext }) {
    const current = brandContext?.current;
    const available = brandContext?.available ?? [];

    if (!current) return null;

    function switchBrand(id) {
        if (id === current.id) return;
        router.post(route('brand.switch', id), {}, { preserveScroll: true });
    }

    return (
        <div className="px-3 py-3">
            <div className="mb-2 px-2 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/40">Brand Aktif</div>
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
                    <DropdownMenuLabel>Pilih Brand</DropdownMenuLabel>
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

export default function AppLayout({ title, header, children }) {
    const { auth, brandContext, flash } = usePage().props;
    const user = auth?.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
        if (flash?.info) toast.message(flash.info);
    }, [flash?.success, flash?.error, flash?.info]);

    return (
        <div className="min-h-screen bg-background">
            <Toaster richColors position="top-right" />

            {/* Sidebar desktop */}
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:block">
                <SidebarContent user={user} brandContext={brandContext} />
            </aside>

            {/* Mobile sidebar */}
            <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                <SheetContent side="left" className="w-72 border-r border-sidebar-border bg-sidebar p-0 text-sidebar-foreground">
                    <SheetTitle className="sr-only">Navigasi</SheetTitle>
                    <SheetDescription className="sr-only">Menu navigasi utama</SheetDescription>
                    <SidebarContent user={user} brandContext={brandContext} onNavigate={() => setMobileOpen(false)} />
                </SheetContent>
            </Sheet>

            <div className="lg:pl-64">
                {/* Top bar */}
                <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b bg-background/80 px-4 backdrop-blur md:px-6">
                    <Button variant="ghost" size="icon" className="lg:hidden" onClick={() => setMobileOpen(true)}>
                        <Menu className="h-5 w-5" />
                    </Button>

                    <div className="flex-1">
                        {header ? (
                            typeof header === 'string' ? (
                                <h1 className="text-base font-semibold sm:text-lg">{header}</h1>
                            ) : (
                                header
                            )
                        ) : title ? (
                            <h1 className="text-base font-semibold sm:text-lg">{title}</h1>
                        ) : null}
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

                    <UserMenu user={user} />
                </header>

                <main className="px-4 py-6 md:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}

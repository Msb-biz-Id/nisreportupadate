import { Check, ChevronsUpDown } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function BrandSwitcher({ brandContext, userRoles = [], isCollapsed }) {
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

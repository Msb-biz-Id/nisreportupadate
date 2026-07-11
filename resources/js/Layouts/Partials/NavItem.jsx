import { useEffect, useState, useRef } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { cn } from '@/lib/utils';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function NavItem({ item, onNavigate, isCollapsed }) {
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

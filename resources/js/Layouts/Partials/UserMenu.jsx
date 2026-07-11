import { Link } from '@inertiajs/react';
import { LogOut, User as UserIcon } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { initials, roleLabel } from '@/lib/utils';

export default function UserMenu({ user }) {
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
                    <Link href={route('logout')} method="post" as="button" className="w-full cursor-pointer text-left">
                        <LogOut className="mr-2 h-4 w-4" /> Keluar
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    Landmark,
    LayoutGrid,
    Shield,
    KeyRound,
    ShieldCheck,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'University Profile',
        href: '/admin/university',
        icon: Landmark,
    },
    {
        title: 'Affiliated Colleges',
        href: '/admin/colleges',
        icon: Building2,
    },
    {
        title: 'Authorized Signatories',
        href: '/admin/university/signatories',
        icon: ShieldCheck,
    },
    { title: 'Users', href: '/admin/users', icon: UsersRound },
    { title: 'Roles', href: '/admin/roles', icon: Shield },
    { title: 'Permissions', href: '/admin/permissions', icon: KeyRound },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage().props;
    const items = mainNavItems.filter((item) => {
        if (item.href === '/admin/university') {
            return auth.permissions.includes('university.view');
        }

        if (item.href === '/admin/colleges') {
            return auth.permissions.includes('college.view');
        }

        if (item.href === '/admin/university/signatories') {
            return auth.permissions.includes('authorized_signatory.view');
        }

        if (item.href === '/admin/users') {
            return auth.permissions.includes('user.view');
        }

        if (item.href === '/admin/roles') {
            return auth.permissions.includes('role.view');
        }

        if (item.href === '/admin/permissions') {
            return auth.permissions.includes('permission.view');
        }

        return true;
    });

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    Landmark,
    LayoutGrid,
    Shield,
    KeyRound,
    ShieldCheck,
    UsersRound,
    ScrollText,
    CalendarRange,
    GraduationCap,
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
    { title: 'Audit Logs', href: '/admin/audit-logs', icon: ScrollText },
    {
        title: 'Academic Sessions',
        href: '/admin/academic-sessions',
        icon: CalendarRange,
    },
    {
        title: 'Degree Levels',
        href: '/admin/degree-levels',
        icon: GraduationCap,
    },
    { title: 'Degrees', href: '/admin/degrees', icon: GraduationCap },
    { title: 'Disciplines', href: '/admin/disciplines', icon: GraduationCap },
    {
        title: 'Program Templates',
        href: '/admin/program-templates',
        icon: GraduationCap,
    },
    {
        title: 'Course Categories',
        href: '/admin/course-categories',
        icon: GraduationCap,
    },
    {
        title: 'Course Types',
        href: '/admin/course-types',
        icon: GraduationCap,
    },
    {
    title: 'Course / Subject Master',
    href: '/admin/courses',
    icon: GraduationCap,
},
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { auth } = usePage().props;
    const collegeId = auth.collegeScopeIds?.[0];

    const scopedItems: NavItem[] = collegeId
        ? [
              ...(auth.permissions.includes('college_user.view')
                  ? [
                        {
                            title: 'College Users',
                            href: `/college/${collegeId}/users`,
                            icon: UsersRound,
                        },
                    ]
                  : []),
              ...(auth.permissions.includes('college_role.view')
                  ? [
                        {
                            title: 'College Roles',
                            href: `/college/${collegeId}/roles`,
                            icon: Shield,
                        },
                    ]
                  : []),
              ...(auth.permissions.includes('college_audit.view')
                  ? [
                        {
                            title: 'College Access Audit',
                            href: `/college/${collegeId}/audit-logs`,
                            icon: ScrollText,
                        },
                    ]
                  : []),
          ]
        : [];

    const items = [
        ...mainNavItems.filter((item) => {
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

            if (item.href === '/admin/audit-logs') {
                return auth.permissions.includes('audit.view');
            }

            if (item.href === '/admin/academic-sessions') {
                return auth.permissions.includes('academic_session.view');
            }

            if (item.href === '/admin/degree-levels') {
                return auth.permissions.includes('degree_level.view');
            }

            if (item.href === '/admin/degrees') {
                return auth.permissions.includes('degree.view');
            }

            if (item.href === '/admin/disciplines') {
                return auth.permissions.includes('discipline.view');
            }

            if (item.href === '/admin/program-templates') {
                return auth.permissions.includes('program_template.view');
            }

            if (item.href === '/admin/course-categories') {
                return auth.permissions.includes('course_category.view');
            }

            if (item.href === '/admin/course-types') {
                return auth.permissions.includes('course_type.view');
            }

            if (item.href === '/admin/courses') {
    return auth.permissions.includes('course.view');
}

            return true;
        }),
        ...scopedItems,
    ];

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

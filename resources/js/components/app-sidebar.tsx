import { Link, usePage } from '@inertiajs/react';
import {
    BookOpenCheck,
    Database,
    Building2,
    CalendarRange,
    ClipboardCheck,
    ChevronRight,
    GraduationCap,
    GitBranch,
    KeyRound,
    Landmark,
    LayoutGrid,
    ScrollText,
    Shield,
    ShieldCheck,
    UsersRound,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ComponentType } from 'react';
import AppLogo from '@/components/app-logo';
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

type TreeNavItem = {
    title: string;
    href?: string;
    icon?: ComponentType<{ className?: string }>;
    permission?: string;
    directNavigation?: boolean;
    children?: TreeNavItem[];
};

type OpenBranches = Record<number, string | null>;

const platformItems: TreeNavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Institution Setup',
        icon: Landmark,
        children: [
            {
                title: 'University Profile',
                href: '/admin/university',
                icon: Landmark,
                permission: 'university.view',
            },
            {
                title: 'Affiliated Colleges',
                href: '/admin/colleges',
                icon: Building2,
                permission: 'college.view',
            },
            {
                title: 'Authorized Signatories',
                href: '/admin/university/signatories',
                icon: ShieldCheck,
                permission: 'authorized_signatory.view',
            },
        ],
    },
    {
        title: 'Access Management',
        icon: Shield,
        children: [
            {
                title: 'Users',
                href: '/admin/users',
                icon: UsersRound,
                permission: 'user.view',
            },
            {
                title: 'Roles',
                href: '/admin/roles',
                icon: Shield,
                permission: 'role.view',
            },
            {
                title: 'Permissions',
                href: '/admin/permissions',
                icon: KeyRound,
                permission: 'permission.view',
            },
            {
                title: 'Audit Logs',
                href: '/admin/audit-logs',
                icon: ScrollText,
                permission: 'audit.view',
            },
        ],
    },
    {
        title: 'Academic Setup',
        icon: GraduationCap,
        children: [
            {
                title: 'Academic Sessions',
                href: '/admin/academic-sessions',
                icon: CalendarRange,
                permission: 'academic_session.view',
            },
            {
                title: 'Degree Structure',
                icon: GraduationCap,
                children: [
                    {
                        title: 'Degree Levels',
                        href: '/admin/degree-levels',
                        permission: 'degree_level.view',
                    },
                    {
                        title: 'Degrees',
                        href: '/admin/degrees',
                        permission: 'degree.view',
                    },
                    {
                        title: 'Disciplines',
                        href: '/admin/disciplines',
                        permission: 'discipline.view',
                    },
                ],
            },
            {
                title: 'Program Setup',
                icon: GraduationCap,
                children: [
                    {
                        title: 'Program Templates',
                        href: '/admin/program-templates',
                        permission: 'program_template.view',
                    },
                ],
            },
            {
                title: 'Course Setup',
                icon: GraduationCap,
                children: [
                    {
                        title: 'Course Categories',
                        href: '/admin/course-categories',
                        permission: 'course_category.view',
                    },
                    {
                        title: 'Course Types',
                        href: '/admin/course-types',
                        permission: 'course_type.view',
                    },
                    {
                        title: 'Course / Subject Master',
                        href: '/admin/courses',
                        permission: 'course.view',
                    },
                ],
            },
            {
                title: 'Curriculum',
                icon: BookOpenCheck,
                href: '/admin/curricula',
                permission: 'curriculum.view',
            },
            {
                title: 'Academic Policies',
                icon: ScrollText,
                href: '/admin/academic-policies',
                permission: 'academic_policy.view',
            },
            {
                title: 'Reservation / Quota Categories',
                icon: ShieldCheck,
                href: '/admin/reservation-categories',
                permission: 'reservation_category.view',
            },
            {
                title: 'Academic Calendar',
                icon: CalendarRange,
                href: '/admin/academic-calendars',
                permission: 'academic_calendar.view',
            },
        ],
    },
    {
        title: 'Academic Approval',
        icon: GitBranch,
        children: [
            {
                title: 'Approval Inbox',
                href: '/admin/academic-approval/inbox',
                icon: ShieldCheck,
                permission: 'approval_request.view',
            },
            {
                title: 'Workflow Setup',
                href: '/admin/academic-approval/workflows',
                icon: GitBranch,
                permission: 'approval_workflow.view',
            },
        ],
    },

    {
        title: 'System Maintenance',
        icon: Database,
        children: [
            {
                title: 'Test Data Cleanup',
                href: '/admin/system-maintenance/test-data-cleanup',
                icon: Database,
                permission: 'test_data_cleanup.manage',
            },
        ],
    },

];


function normalizePath(path: string): string {
    const cleanPath = path.split('?')[0].split('#')[0];

    if (cleanPath.length > 1 && cleanPath.endsWith('/')) {
        return cleanPath.slice(0, -1);
    }

    return cleanPath;
}

function isLeafActive(currentPath: string, href?: string): boolean {
    if (!href) {
        return false;
    }

    const target = normalizePath(href);

    return currentPath === target || currentPath.startsWith(`${target}/`);
}

function isBranchActive(item: TreeNavItem, currentPath: string): boolean {
    if (isLeafActive(currentPath, item.href)) {
        return true;
    }

    return (
        item.children?.some((child) => isBranchActive(child, currentPath)) ??
        false
    );
}

function filterByPermission(
    items: TreeNavItem[],
    permissions: string[],
): TreeNavItem[] {
    return items.flatMap((item) => {
        const children = item.children
            ? filterByPermission(item.children, permissions)
            : undefined;

        if (item.permission && !permissions.includes(item.permission)) {
            return [];
        }

        if (item.children && (!children || children.length === 0)) {
            return [];
        }

        return [
            {
                ...item,
                children,
            },
        ];
    });
}

function getActiveBranchPath(
    items: TreeNavItem[],
    currentPath: string,
    depth = 0,
    parentPath: string[] = [],
): OpenBranches {
    for (const item of items) {
        const itemPath = [...parentPath, item.title];
        const branchKey = itemPath.join(' > ');

        if (item.children?.length && isBranchActive(item, currentPath)) {
            return {
                [depth]: branchKey,
                ...getActiveBranchPath(
                    item.children,
                    currentPath,
                    depth + 1,
                    itemPath,
                ),
            };
        }
    }

    return {};
}

function clearDeeperBranches(
    branches: OpenBranches,
    depth: number,
): OpenBranches {
    return Object.fromEntries(
        Object.entries(branches).filter(([level]) => Number(level) <= depth),
    );
}

function TreeItem({
    item,
    currentPath,
    depth,
    parentPath,
    openBranches,
    onToggle,
}: {
    item: TreeNavItem;
    currentPath: string;
    depth: number;
    parentPath: string[];
    openBranches: OpenBranches;
    onToggle: (depth: number, branchKey: string) => void;
}) {
    const Icon = item.icon;
    const hasChildren = Boolean(item.children?.length);
    const itemPath = [...parentPath, item.title];
    const branchKey = itemPath.join(' > ');
    const isOpen = hasChildren && openBranches[depth] === branchKey;
    const isActiveLeaf = isLeafActive(currentPath, item.href);
    const isActiveBranch = hasChildren && isBranchActive(item, currentPath);
    const nested = depth > 0;

    const connectorClass = nested
        ? 'relative before:absolute before:-left-3 before:top-4 before:w-3 before:border-t before:border-sidebar-border'
        : '';

    if (!hasChildren && item.href) {
        return (
            <SidebarMenuItem className={connectorClass}>
                <SidebarMenuButton
                    asChild
                    isActive={isActiveLeaf}
                    tooltip={item.title}
                    className="transition-colors duration-200 ease-out motion-reduce:transition-none"
                >
                    {item.directNavigation ? (
                        <a href={item.href}>
                            {Icon ? <Icon className="size-4 shrink-0" /> : null}
                            <span>{item.title}</span>
                        </a>
                    ) : (
                        <Link href={item.href} prefetch>
                            {Icon ? <Icon className="size-4 shrink-0" /> : null}
                            <span>{item.title}</span>
                        </Link>
                    )}
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuItem className={connectorClass}>
            <SidebarMenuButton
                type="button"
                tooltip={item.title}
                aria-expanded={isOpen}
                onClick={() => onToggle(depth, branchKey)}
                className={[
                    'transition-colors duration-200 ease-out motion-reduce:transition-none',
                    isOpen || isActiveBranch
                        ? 'bg-sidebar-accent/60 text-sidebar-accent-foreground'
                        : '',
                ]
                    .filter(Boolean)
                    .join(' ')}
            >
                {Icon ? <Icon className="size-4 shrink-0" /> : null}
                <span>{item.title}</span>
                <ChevronRight
                    className={[
                        'ml-auto size-4 shrink-0 transition-transform duration-250 ease-out motion-reduce:transition-none',
                        isOpen ? 'rotate-90' : '',
                    ]
                        .filter(Boolean)
                        .join(' ')}
                />
            </SidebarMenuButton>

            <div
                className={[
                    'grid transition-[grid-template-rows,opacity] duration-300 ease-out motion-reduce:transition-none',
                    isOpen
                        ? 'grid-rows-[1fr] opacity-100'
                        : 'grid-rows-[0fr] opacity-0',
                ].join(' ')}
                aria-hidden={!isOpen}
            >
                <div className="overflow-hidden">
                    <div
                        className={[
                            'relative ml-4 border-l border-sidebar-border pl-3 pt-1',
                            'transition-transform duration-300 ease-out motion-reduce:transition-none',
                            isOpen ? 'translate-y-0' : '-translate-y-1',
                        ].join(' ')}
                    >
                        <SidebarMenu>
                            {item.children?.map((child) => (
                                <TreeItem
                                    key={`${branchKey}-${child.title}`}
                                    item={child}
                                    currentPath={currentPath}
                                    depth={depth + 1}
                                    parentPath={itemPath}
                                    openBranches={openBranches}
                                    onToggle={onToggle}
                                />
                            ))}
                        </SidebarMenu>
                    </div>
                </div>
            </div>
        </SidebarMenuItem>
    );
}


function SidebarTree({
    items,
    currentPath,
    initialOpenBranches,
}: {
    items: TreeNavItem[];
    currentPath: string;
    initialOpenBranches: OpenBranches;
}) {
    const [openBranches, setOpenBranches] =
        useState<OpenBranches>(initialOpenBranches);

    const handleToggle = (depth: number, branchKey: string) => {
        setOpenBranches((current) => {
            const next = clearDeeperBranches(current, depth);

            next[depth] = current[depth] === branchKey ? null : branchKey;

            return next;
        });
    };

    return (
        <SidebarMenu>
            {items.map((item) => (
                <TreeItem
                    key={item.title}
                    item={item}
                    currentPath={currentPath}
                    depth={0}
                    parentPath={[]}
                    openBranches={openBranches}
                    onToggle={handleToggle}
                />
            ))}
        </SidebarMenu>
    );
}

export function AppSidebar() {
    const page = usePage();
    const { auth } = page.props;
    const currentPath = normalizePath(page.url);
    const collegeId = auth.collegeScopeIds?.[0];

    const collegeItems: TreeNavItem[] = collegeId
        ? [
              {
                  title: 'College Management',
                  icon: Building2,
                  children: [
                      {
                          title: 'College Users',
                          href: `/college/${collegeId}/users`,
                          icon: UsersRound,
                          permission: 'college_user.view',
                      },
                      {
                          title: 'College Roles',
                          href: `/college/${collegeId}/roles`,
                          icon: Shield,
                          permission: 'college_role.view',
                      },
                      {
                          title: 'College Access Audit',
                          href: `/college/${collegeId}/audit-logs`,
                          icon: ScrollText,
                          permission: 'college_audit.view',
                      },
                  ],
              },
              {
                  title: 'College Academic Setup',
                  icon: GraduationCap,
                  children: [
                      {
                          title: 'Program Offerings',
                          href: `/college/${collegeId}/program-offerings`,
                          icon: BookOpenCheck,
                          permission: 'college_program_offering.view',
                      },
                      {
                          title: 'Intake / Seat Capacity',
                          href: `/college/${collegeId}/intakes`,
                          icon: GraduationCap,
                          permission: 'college_program_intake.view',
                      },
                      {
                          title: 'Reservation / Seat Distribution',
                          href: `/college/${collegeId}/reservations`,
                          icon: ShieldCheck,
                          permission: 'college_reservation.view',
                      },
                      {
                          title: 'Merit / Roster / Selection Rules',
                          href: `/college/${collegeId}/admission-selection-rules`,
                          icon: GitBranch,
                          permission: 'college_admission_selection_rule.view',
                      },
                      {
                          title: 'Admission Cycle',
                          href: `/college/${collegeId}/admission-cycles`,
                          icon: CalendarRange,
                          permission: 'college_admission_cycle.view',
                      },
                      {
                          title: 'Applications / Candidate Eligibility',
                          href: `/college/${collegeId}/admission-applications`,
                          icon: ClipboardCheck,
                          permission: 'college_admission_application.view',
                      },
                      {
                          title: 'Score Capture / Normalization',
                          href: `/college/${collegeId}/admission-scores`,
                          icon: ClipboardCheck,
                          permission: 'college_admission_score.view',
                          directNavigation: true,
                      },
                  ],
              },
          ]
        : [];

    const items = useMemo(
        () =>
            filterByPermission(
                [...platformItems, ...collegeItems],
                auth.permissions,
            ),
        [collegeId, auth.permissions],
    );

    const activeBranches = useMemo(
        () => getActiveBranchPath(items, currentPath),
        [items, currentPath],
    );

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
                <div className="px-2 py-2">
                    <div className="px-2 pb-2 text-xs font-medium text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden">
                        Platform
                    </div>

                    <SidebarTree
                        key={currentPath}
                        items={items}
                        currentPath={currentPath}
                        initialOpenBranches={activeBranches}
                    />
                </div>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardCheck, FileText, FolderGit2, KeyRound, LayoutGrid, ListChecks, Settings, ShieldCheck } from 'lucide-react';
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

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props as {
        auth?: {
            guard?: string;
            permissions?: string[];
        };
    };

    const isAdmin = auth?.guard === 'admin';
    const permissions = auth?.permissions ?? [];
    const can = (permission: string) => permissions.includes(permission);

    const mainNavItems: NavItem[] = isAdmin
        ? [
              {
                  title: 'Admin Dashboard',
                  href: '/admin/dashboard',
                  icon: LayoutGrid,
              },
              ...(can('kyc.review')
                  ? [{ title: 'KYC', href: '/admin/kyc', icon: ClipboardCheck }]
                  : []),
              ...(can('operations.view')
                  ? [{ title: 'Operations', href: '/admin/operations', icon: ListChecks }]
                  : []),
              ...(can('audit.view')
                  ? [{ title: 'Audit', href: '/admin/audit', icon: FileText }]
                  : []),
              ...(can('settings.manage')
                  ? [{ title: 'Parametres', href: '/admin/settings', icon: Settings }]
                  : []),
              ...(can('roles.manage')
                  ? [{ title: 'Roles', href: '/admin/roles', icon: ShieldCheck }]
                  : []),
              ...(can('permissions.manage')
                  ? [
                        {
                            title: 'Permissions',
                            href: '/admin/permissions',
                            icon: KeyRound,
                        },
                    ]
                  : []),
          ]
        : [
              {
                  title: 'Dashboard',
                  href: dashboard(),
                  icon: LayoutGrid,
              },
          ];

    const homeHref = isAdmin ? '/admin/dashboard' : dashboard();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

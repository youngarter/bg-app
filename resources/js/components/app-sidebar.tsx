import { Link, usePage } from '@inertiajs/react';
import { Building2, LayoutGrid, Settings, Users } from 'lucide-react';
import { AppBrand } from '@/components/app-brand';
import { NavMain } from '@/components/nav-main';
import type { NavSectionGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { ResidenceSelector } from '@/components/residence-selector';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    useSidebar,
} from '@/components/ui/sidebar';

export function AppSidebar() {
    const { props } = usePage<{
        currentRole?: string | null;
        residence?: { id: number; nom: string } | null;
    }>();
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    const hasResidence = !!props.residence;
    const isCoproprietaire = props.currentRole === 'coproprietaire';

    const navGroups: NavSectionGroup[] = [
        {
            title: 'Pilotage',
            items: [
                {
                    title: 'Tableau de bord',
                    href: '/dashboard',
                    icon: LayoutGrid,
                },
            ],
        },
        ...(hasResidence && !isCoproprietaire
            ? [
                  {
                      title: 'Copropriété',
                      items: [
                          {
                              title: 'Lots & Tantièmes',
                              href: '/lots',
                              icon: Building2,
                          },
                          {
                              title: 'Copropriétaires',
                              href: '/owners',
                              icon: Users,
                          },
                      ],
                  },
              ]
            : []),
        {
            title: 'Configuration',
            items: [
                {
                    title: 'Paramètres',
                    href: '/settings/profile',
                    icon: Settings,
                },
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader className="space-y-2 border-b border-sidebar-border/70 p-3">
                <div
                    className={`flex items-center ${isCollapsed ? 'justify-center' : 'px-1 py-0.5'}`}
                >
                    <Link
                        href="/dashboard"
                        prefetch
                        className="rounded-lg focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                    >
                        <AppBrand compact={isCollapsed} />
                    </Link>
                </div>
                <ResidenceSelector />
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

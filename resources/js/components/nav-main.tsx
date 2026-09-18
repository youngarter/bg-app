import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';

export interface NavSectionItem {
    title: string;
    href: string;
    icon: LucideIcon;
    badge?: string;
}

export interface NavSectionGroup {
    title: string;
    items: NavSectionItem[];
}

export function NavMain({ groups = [] }: { groups: NavSectionGroup[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    return (
        <div className="space-y-4 px-2 py-3">
            {groups.map((group) => {
                if (group.items.length === 0) {
                    return null;
                }

                return (
                    <SidebarGroup key={group.title} className="p-0">
                        {!isCollapsed && (
                            <SidebarGroupLabel className="px-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground/80 mb-1 h-auto py-1">
                                {group.title}
                            </SidebarGroupLabel>
                        )}
                        <SidebarMenu className="gap-1">
                            {group.items.map((item) => {
                                const active = isCurrentUrl(item.href);

                                return (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={active}
                                            tooltip={{ children: item.title }}
                                            className={`rounded-xl transition-all duration-150 ${
                                                active
                                                    ? 'bg-primary/10 text-primary font-semibold border border-primary/20 shadow-2xs hover:bg-primary/15'
                                                    : 'text-foreground/75 hover:bg-muted/60 hover:text-foreground'
                                            }`}
                                        >
                                            <Link href={item.href} prefetch className="flex items-center gap-2.5">
                                                <item.icon className={`size-4 shrink-0 ${active ? 'text-primary' : 'text-muted-foreground'}`} />
                                                <span className="truncate">{item.title}</span>
                                                {item.badge && !isCollapsed && (
                                                    <span className={`ml-auto rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide ${
                                                        active
                                                            ? 'bg-primary/20 text-primary'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}>
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroup>
                );
            })}
        </div>
    );
}

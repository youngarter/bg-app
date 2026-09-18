import { usePage } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/hooks/use-appearance';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import type { Residence } from '@/types/bayan';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { props } = usePage<{
        residence?: Residence | null;
        currentRole?: string | null;
    }>();
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const toggleTheme = () => {
        updateAppearance(resolvedAppearance === 'dark' ? 'light' : 'dark');
    };

    const residence = props.residence;

    return (
        <header className="flex h-14 shrink-0 items-center justify-between border-b border-border/70 px-4 sm:px-6 bg-background/80 backdrop-blur-md sticky top-0 z-10">
            <div className="flex items-center gap-3 min-w-0">
                <SidebarTrigger className="-ml-1 text-muted-foreground hover:text-foreground" />
                <div className="h-4 w-px bg-border/60" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex items-center gap-2.5">
                {residence && (
                    <div className="hidden md:flex items-center gap-2 rounded-full border border-border/70 bg-card/60 px-3 py-1 text-xs shadow-2xs">
                        <span className="size-1.5 rounded-full bg-ok" />
                        <span className="font-medium text-foreground truncate max-w-[160px]">
                            {residence.nom}
                        </span>
                        {residence.ville && (
                            <span className="text-muted-foreground text-[11px]">
                                • {residence.ville}
                            </span>
                        )}
                    </div>
                )}

                <Button
                    variant="ghost"
                    size="icon"
                    onClick={toggleTheme}
                    className="size-8 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted/60"
                    title={resolvedAppearance === 'dark' ? 'Passer au mode clair' : 'Passer au mode sombre'}
                    aria-label="Basculer le thème"
                >
                    {resolvedAppearance === 'dark' ? (
                        <Sun className="size-4 text-warn" />
                    ) : (
                        <Moon className="size-4 text-foreground/80" />
                    )}
                </Button>
            </div>
        </header>
    );
}

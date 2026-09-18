import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useSidebar } from '@/components/ui/sidebar';
import type { AccessibleResidence, Residence } from '@/types/bayan';

function getInitials(name: string): string {
    const cleaned = name.replace(/^Résidence\s+/i, '').trim();
    const parts = cleaned.split(/\s+/).filter(Boolean);

    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }

    return (cleaned.slice(0, 2) || 'CO').toUpperCase();
}

export function ResidenceSelector() {
    const { props } = usePage<{
        residence?: Residence | null;
        accessibleResidences?: AccessibleResidence[];
    }>();
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    const currentResidence = props.residence;
    const residences = props.accessibleResidences ?? [];

    if (residences.length === 0) {
        return null;
    }

    const activeNom = currentResidence?.nom || residences[0]?.nom || 'Copropriété';
    const activeVille = currentResidence?.ville || residences[0]?.ville;
    const initials = getInitials(activeNom);

    // Si une seule résidence accessible
    if (residences.length <= 1) {
        if (isCollapsed) {
            return (
                <div className="flex justify-center py-1">
                    <div
                        title={`${activeNom}${activeVille ? ` (${activeVille})` : ''}`}
                        className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-display text-xs font-bold text-primary border border-primary/20 shadow-2xs"
                    >
                        {initials}
                    </div>
                </div>
            );
        }

        return (
            <div className="flex items-center gap-2.5 rounded-xl border border-border/70 bg-card p-2 shadow-2xs">
                <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-display text-xs font-bold text-primary border border-primary/20">
                    {initials}
                </div>
                <div className="flex flex-col min-w-0 flex-1">
                    <span className="truncate font-semibold text-xs text-foreground leading-tight">
                        {activeNom}
                    </span>
                    {activeVille && (
                        <span className="truncate text-[10px] text-muted-foreground mt-0.5">
                            {activeVille}
                        </span>
                    )}
                </div>
                <span className="rounded bg-ok-surface px-1.5 py-0.5 text-[9px] font-bold text-ok uppercase">
                    Active
                </span>
            </div>
        );
    }

    const handleSelect = (residenceId: number) => {
        if (residenceId === currentResidence?.id) {
            return;
        }

        router.post(`/residences/${residenceId}/select`, {}, {
            preserveState: false,
        });
    };

    if (isCollapsed) {
        return (
            <div className="flex justify-center py-1">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            title={`${activeNom}${activeVille ? ` (${activeVille})` : ''}`}
                            className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-display text-xs font-bold text-primary border border-primary/20 shadow-2xs hover:bg-primary/20 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            {initials}
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" side="right" className="w-64 rounded-xl shadow-lg border-border">
                        <DropdownMenuLabel className="text-[11px] font-semibold text-muted-foreground flex items-center justify-between px-3 py-2">
                            <span>Mes copropriétés gérées</span>
                            <span className="font-mono text-[10px] text-primary">{residences.length}</span>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {residences.map((r) => {
                            const isSelected = r.id === currentResidence?.id;
                            const rInitials = getInitials(r.nom);

                            return (
                                <DropdownMenuItem
                                    key={r.id}
                                    onClick={() => handleSelect(r.id)}
                                    className="flex items-center justify-between text-xs cursor-pointer py-2 px-3 rounded-lg hover:bg-muted/60"
                                >
                                    <div className="flex items-center gap-2.5 min-w-0">
                                        <div className="flex size-6 shrink-0 items-center justify-center rounded-md bg-muted text-[10px] font-bold text-muted-foreground">
                                            {rInitials}
                                        </div>
                                        <div className="flex flex-col min-w-0">
                                            <span className={`truncate ${isSelected ? 'font-semibold text-primary' : 'text-foreground'}`}>
                                                {r.nom}
                                            </span>
                                            {r.ville && (
                                                <span className="text-[10px] text-muted-foreground truncate">
                                                    {r.ville}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    {isSelected && (
                                        <Check className="size-3.5 text-primary shrink-0 ml-2" />
                                    )}
                                </DropdownMenuItem>
                            );
                        })}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="flex w-full items-center justify-between gap-2.5 rounded-xl border border-border/80 bg-card p-2 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring shadow-2xs"
                >
                    <div className="flex items-center gap-2.5 min-w-0 flex-1">
                        <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-display text-xs font-bold text-primary border border-primary/20">
                            {initials}
                        </div>
                        <div className="flex flex-col min-w-0 flex-1">
                            <span className="truncate font-semibold text-xs text-foreground leading-tight">
                                {activeNom}
                            </span>
                            {activeVille && (
                                <span className="truncate text-[10px] text-muted-foreground mt-0.5">
                                    {activeVille}
                                </span>
                            )}
                        </div>
                    </div>
                    <ChevronsUpDown className="size-3.5 shrink-0 text-muted-foreground/70" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-64 rounded-xl shadow-lg border-border">
                <DropdownMenuLabel className="text-[11px] font-semibold text-muted-foreground flex items-center justify-between px-3 py-2">
                    <span>Mes copropriétés gérées</span>
                    <span className="font-mono text-[10px] text-primary">{residences.length}</span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {residences.map((r) => {
                    const isSelected = r.id === currentResidence?.id;
                    const rInitials = getInitials(r.nom);

                    return (
                        <DropdownMenuItem
                            key={r.id}
                            onClick={() => handleSelect(r.id)}
                            className="flex items-center justify-between text-xs cursor-pointer py-2 px-3 rounded-lg hover:bg-muted/60"
                        >
                            <div className="flex items-center gap-2.5 min-w-0">
                                <div className="flex size-6 shrink-0 items-center justify-center rounded-md bg-muted text-[10px] font-bold text-muted-foreground">
                                    {rInitials}
                                </div>
                                <div className="flex flex-col min-w-0">
                                    <span className={`truncate ${isSelected ? 'font-semibold text-primary' : 'text-foreground'}`}>
                                        {r.nom}
                                    </span>
                                    {r.ville && (
                                        <span className="text-[10px] text-muted-foreground truncate">
                                            {r.ville}
                                        </span>
                                    )}
                                </div>
                            </div>
                            {isSelected && (
                                <Check className="size-3.5 text-primary shrink-0 ml-2" />
                            )}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

import { Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    Building2,
    Calendar,
    ChevronRight,
    Layers,
    PlusCircle,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { OnboardingStepper } from '@/components/onboarding-stepper';
import { formatDate, formatInteger } from '@/lib/formatters';
import type { OnboardingData, Residence } from '@/types/bayan';

interface DashboardProps {
    hasNoResidences: boolean;
    residence: Residence | null;
    structure: {
        active_mandate: {
            id: number;
            syndic_nom: string;
            started_on: string;
            ended_on: string;
            pv_ag_reference: string | null;
        } | null;
        open_exercise: {
            id: number;
            label: string;
            started_on: string;
            ended_on: string;
        } | null;
        lots_count: number;
        owners_count: number;
        sum_tantiemes: number;
        total_tantiemes: number;
        tantiemes_consistent: boolean;
    } | null;
    onboarding: OnboardingData | null;
    isStructureEmpty: boolean;
}

export default function Dashboard({
    hasNoResidences,
    residence,
    structure,
    onboarding,
    isStructureEmpty,
}: DashboardProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tableau de bord', href: '/dashboard' },
        ],
    });

    if (hasNoResidences || !residence) {
        return (
            <>
                <Head title="Tableau de bord" />
                <div className="flex flex-1 items-center justify-center p-6">
                    <div className="max-w-md text-center">
                        <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-primary/20 shadow-xs">
                            <Building2 className="size-8" />
                        </div>
                        <h1 className="mb-2 font-display text-xl font-bold text-foreground">
                            Aucune copropriété associée
                        </h1>
                        <p className="mb-6 text-sm text-muted-foreground leading-relaxed">
                            Vous n'êtes actuellement rattaché à aucune
                            résidence. Si vous êtes gestionnaire ou
                            copropriétaire, contactez l'administrateur de votre
                            résidence pour obtenir vos accès.
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`Tableau de bord — ${residence.nom}`} />

            <div className="flex w-full flex-1 flex-col gap-6 p-4 sm:p-6 md:p-8">
                {/* Cockpit Title & Residence Identity */}
                <div className="flex flex-col justify-between gap-4 border-b border-border/70 pb-5 sm:flex-row sm:items-center">
                    <div>
                        <div className="mb-1 flex flex-wrap items-center gap-2.5">
                            <h1 className="font-display text-2xl sm:text-3xl font-bold tracking-tight text-foreground">
                                {residence.nom}
                            </h1>
                            <span className="inline-flex items-center rounded-full border border-border/80 bg-muted/60 px-3 py-1 font-mono text-xs font-semibold text-muted-foreground">
                                {formatInteger(residence.total_tantiemes)} tantièmes
                            </span>
                        </div>
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            {residence.adresse && <span>{residence.adresse}</span>}
                            {residence.ville && (
                                <>
                                    <span>•</span>
                                    <span>{residence.ville}</span>
                                </>
                            )}
                            {residence.code_postal && (
                                <span className="font-mono text-xs text-muted-foreground/80">
                                    ({residence.code_postal})
                                </span>
                            )}
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Link
                            href="/lots"
                            prefetch
                            className="inline-flex items-center gap-2 rounded-xl border border-border/80 bg-card px-3.5 py-2 text-xs font-semibold text-foreground shadow-2xs transition-all hover:bg-muted/70 hover:border-border"
                        >
                            <Building2 className="size-3.5 text-primary" />
                            <span>Lots & Tantièmes</span>
                        </Link>
                        <Link
                            href="/owners"
                            prefetch
                            className="inline-flex items-center gap-2 rounded-xl border border-border/80 bg-card px-3.5 py-2 text-xs font-semibold text-foreground shadow-2xs transition-all hover:bg-muted/70 hover:border-border"
                        >
                            <Users className="size-3.5 text-primary" />
                            <span>Copropriétaires</span>
                        </Link>
                    </div>
                </div>

                {/* Parcours d'initialisation guidé */}
                <OnboardingStepper onboarding={onboarding} />

                {/* État vide si structure entièrement vierge */}
                {isStructureEmpty ? (
                    <div className="rounded-2xl border border-border/80 bg-card p-10 text-center shadow-xs">
                        <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-primary/20 shadow-xs">
                            <Layers className="size-7" />
                        </div>
                        <h3 className="mb-2 font-display text-lg font-bold text-foreground">
                            Configuration de l'état de division
                        </h3>
                        <p className="mx-auto mb-6 max-w-lg text-sm text-muted-foreground leading-relaxed">
                            Cette résidence ne contient encore aucun lot ni
                            mandat actif. Suivez les étapes de configuration ci-dessus
                            pour saisir les lots et constituer l'état de
                            division selon la loi 18-00.
                        </p>
                        <Link
                            href="/lots"
                            prefetch
                            className="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                        >
                            <PlusCircle className="size-4" />
                            <span>Créer le premier lot</span>
                        </Link>
                    </div>
                ) : (
                    /* Grille de synthèse structurelle (zéro finance) */
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {/* Mandat actif */}
                        <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-4.5 shadow-2xs hover:shadow-xs transition-all">
                            <div>
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <div className="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <ShieldCheck className="size-4" />
                                        </div>
                                        <span className="text-xs font-semibold text-muted-foreground">
                                            Mandat de syndic
                                        </span>
                                    </div>
                                    {structure?.active_mandate ? (
                                        <span className="inline-flex items-center rounded-full border border-ok/30 bg-ok-surface px-2.5 py-0.5 text-[10px] font-bold text-ok uppercase tracking-wider">
                                            Actif
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-full border border-warn/30 bg-warn-surface px-2.5 py-0.5 text-[10px] font-bold text-warn uppercase tracking-wider">
                                            Aucun
                                        </span>
                                    )}
                                </div>
                                {structure?.active_mandate ? (
                                    <div>
                                        <div className="truncate font-display text-base font-bold text-foreground">
                                            {structure.active_mandate.syndic_nom}
                                        </div>
                                        <div className="mt-1 font-mono text-xs text-muted-foreground">
                                            {formatDate(
                                                structure.active_mandate.started_on,
                                            )}{' '}
                                            →{' '}
                                            {formatDate(
                                                structure.active_mandate.ended_on,
                                            )}
                                        </div>
                                        {structure.active_mandate.pv_ag_reference && (
                                            <div className="mt-1.5 truncate text-[11px] text-muted-foreground/80">
                                                Réf. PV :{' '}
                                                <span className="font-mono">{structure.active_mandate.pv_ag_reference}</span>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground italic py-1">
                                        Aucun mandat actif enregistré.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Exercice comptable ouvert */}
                        <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-4.5 shadow-2xs hover:shadow-xs transition-all">
                            <div>
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <div className="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <Calendar className="size-4" />
                                        </div>
                                        <span className="text-xs font-semibold text-muted-foreground">
                                            Exercice comptable
                                        </span>
                                    </div>
                                    {structure?.open_exercise ? (
                                        <span className="inline-flex items-center rounded-full border border-ok/30 bg-ok-surface px-2.5 py-0.5 text-[10px] font-bold text-ok uppercase tracking-wider">
                                            Ouvert
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
                                            Aucun
                                        </span>
                                    )}
                                </div>
                                {structure?.open_exercise ? (
                                    <div>
                                        <div className="font-display text-base font-bold text-foreground">
                                            Exercice {structure.open_exercise.label}
                                        </div>
                                        <div className="mt-1 font-mono text-xs text-muted-foreground">
                                            {formatDate(
                                                structure.open_exercise.started_on,
                                            )}{' '}
                                            →{' '}
                                            {formatDate(
                                                structure.open_exercise.ended_on,
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground italic py-1">
                                        Aucun exercice comptable ouvert.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Lots de copropriété */}
                        <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-4.5 shadow-2xs hover:shadow-xs transition-all">
                            <div>
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <div className="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <Building2 className="size-4" />
                                        </div>
                                        <span className="text-xs font-semibold text-muted-foreground">
                                            Lots de division
                                        </span>
                                    </div>
                                    <Link
                                        href="/lots"
                                        prefetch
                                        className="text-muted-foreground hover:text-primary transition-colors"
                                    >
                                        <ChevronRight className="size-4" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="font-display text-2xl font-bold tracking-tight text-foreground">
                                        {structure?.lots_count ?? 0}
                                    </div>
                                    <div className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground font-mono">
                                        <span>
                                            {formatInteger(structure?.sum_tantiemes)}
                                        </span>
                                        <span>/</span>
                                        <span>
                                            {formatInteger(structure?.total_tantiemes)}{' '}
                                            tantièmes
                                        </span>
                                    </div>
                                    {structure &&
                                        !structure.tantiemes_consistent &&
                                        structure.lots_count > 0 && (
                                            <div className="mt-1.5 inline-flex items-center rounded-md bg-warn-surface px-2 py-0.5 text-[10px] font-semibold text-warn border border-warn/20">
                                                Écart : {formatInteger(structure.total_tantiemes - structure.sum_tantiemes)} tantièmes
                                            </div>
                                        )}
                                </div>
                            </div>
                        </div>

                        {/* Copropriétaires */}
                        <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-4.5 shadow-2xs hover:shadow-xs transition-all">
                            <div>
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <div className="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <Users className="size-4" />
                                        </div>
                                        <span className="text-xs font-semibold text-muted-foreground">
                                            Copropriétaires
                                        </span>
                                    </div>
                                    <Link
                                        href="/owners"
                                        prefetch
                                        className="text-muted-foreground hover:text-primary transition-colors"
                                    >
                                        <ChevronRight className="size-4" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="font-display text-2xl font-bold tracking-tight text-foreground">
                                        {structure?.owners_count ?? 0}
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Personnes physiques, morales & indivisions
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

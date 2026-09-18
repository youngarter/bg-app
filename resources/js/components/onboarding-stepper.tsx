import { Link } from '@inertiajs/react';
import { AlertCircle, ArrowRight, CheckCircle2 } from 'lucide-react';
import { formatDecimal, formatInteger } from '@/lib/formatters';
import type { OnboardingData } from '@/types/bayan';

interface OnboardingStepperProps {
    onboarding: OnboardingData | null;
}

export function OnboardingStepper({ onboarding }: OnboardingStepperProps) {
    if (!onboarding || onboarding.is_complete) {
        return null;
    }

    const completedCount = onboarding.steps.filter((s) => s.completed).length;
    const totalCount = onboarding.steps.length;
    const progressPercent = Math.round((completedCount / totalCount) * 100);

    // Find the first uncompleted step as the "current" active step
    const activeStepIndex = onboarding.steps.findIndex((s) => !s.completed);

    return (
        <div className="mb-6 rounded-2xl border border-border/80 bg-card p-5 shadow-sm transition-all">
            <div className="mb-5 flex flex-col justify-between gap-3 border-b border-border/70 pb-4 sm:flex-row sm:items-center">
                <div>
                    <div className="flex items-center gap-2">
                        <h2 className="font-display text-base font-semibold text-foreground tracking-tight">
                            Initialisation de la copropriété
                        </h2>
                        <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-semibold text-primary">
                            {completedCount} / {totalCount} validées
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Structure juridique et technique requise avant l'ouverture des comptes financiers.
                    </p>
                </div>

                <div className="flex items-center gap-3">
                    <div className="w-32 sm:w-44">
                        <div className="flex justify-between text-[11px] text-muted-foreground mb-1 font-mono">
                            <span>Progression</span>
                            <span className="font-semibold text-foreground">{progressPercent}%</span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full bg-primary transition-all duration-300 rounded-full"
                                style={{ width: `${progressPercent}%` }}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
                {onboarding.steps.map((step, idx) => {
                    const isStep5 = step.key === 'ownerships';
                    const hasDiscrepancy = isStep5 && !step.completed && (step.discrepancy ?? 0) !== 0;
                    const isCurrent = idx === activeStepIndex;

                    return (
                        <div
                            key={step.key}
                            className={`flex flex-col justify-between rounded-xl border p-3.5 text-xs transition-all relative ${
                                step.completed
                                    ? 'border-ok/30 bg-ok-surface/20'
                                    : isCurrent
                                      ? 'border-primary/40 bg-primary/[0.02] shadow-2xs ring-1 ring-primary/20'
                                      : 'border-border/70 bg-card/50 opacity-80'
                            }`}
                        >
                            <div>
                                <div className="mb-2 flex items-center justify-between gap-1.5">
                                    <span
                                        className={`flex size-5 shrink-0 items-center justify-center rounded-full font-mono text-[10px] font-bold ${
                                            step.completed
                                                ? 'bg-ok-surface text-ok'
                                                : isCurrent
                                                  ? 'bg-primary text-white'
                                                  : 'bg-muted text-muted-foreground'
                                        }`}
                                    >
                                        {step.completed ? (
                                            <CheckCircle2 className="size-3.5" />
                                        ) : (
                                            step.index
                                        )}
                                    </span>

                                    {step.completed ? (
                                        <span className="text-[10px] font-semibold text-ok uppercase tracking-wider">
                                            Validé
                                        </span>
                                    ) : isCurrent ? (
                                        <span className="rounded bg-primary/10 px-1.5 py-0.5 text-[9px] font-bold text-primary uppercase tracking-wider">
                                            À faire
                                        </span>
                                    ) : (
                                        <span className="text-[10px] text-muted-foreground/60 uppercase tracking-wider">
                                            En attente
                                        </span>
                                    )}
                                </div>

                                <h3 className="font-semibold text-foreground text-xs leading-snug">
                                    {step.title}
                                </h3>

                                <p className="mt-1 line-clamp-2 text-[11px] text-muted-foreground leading-relaxed">
                                    {step.description}
                                </p>

                                {hasDiscrepancy && (
                                    <div className="mt-2.5 rounded-lg border border-warn/30 bg-warn-surface p-2 text-[10px] text-warn flex items-start gap-1.5">
                                        <AlertCircle className="size-3.5 shrink-0 mt-0.5" />
                                        <div>
                                            <span className="font-semibold">Écart de répartition :</span>{' '}
                                            <span className="font-mono">{formatInteger(step.discrepancy!)}</span> tantièmes (
                                            <span className="font-mono">{formatDecimal(step.discrepancy_percentage!)} %</span>)
                                        </div>
                                    </div>
                                )}
                            </div>

                            {!step.completed && (
                                <div className="mt-3 border-t border-border/50 pt-2.5">
                                    <Link
                                        href={step.href}
                                        className={`inline-flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-[11px] font-medium transition-colors ${
                                            isCurrent
                                                ? 'bg-primary text-primary-foreground shadow-2xs hover:bg-primary/90'
                                                : 'bg-muted/70 text-foreground/80 hover:bg-muted'
                                        }`}
                                    >
                                        <span>Configurer</span>
                                        <ArrowRight className="size-3" />
                                    </Link>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

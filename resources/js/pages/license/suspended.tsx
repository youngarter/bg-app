import { Head, Link } from '@inertiajs/react';
import { AlertOctagon, ArrowLeft } from 'lucide-react';

interface SuspendedProps {
    residence: {
        id: number;
        nom: string;
    } | null;
}

export default function Suspended({ residence }: SuspendedProps) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6">
            <Head title="Copropriété suspendue" />

            <div className="w-full max-w-md rounded-xl border border-crit/30 bg-card p-6 shadow-sm text-center">
                <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-crit-surface text-crit mb-4 border border-crit/20">
                    <AlertOctagon className="size-7" />
                </div>

                <h1 className="font-display text-xl font-bold text-foreground mb-2">
                    Accès suspendu
                </h1>

                <p className="text-xs text-crit font-semibold mb-3">
                    {residence ? `Résidence ${residence.nom}` : 'Cette copropriété'}
                </p>

                <p className="text-xs text-muted-foreground leading-relaxed mb-6">
                    La licence d'exploitation de cette copropriété a été suspendue par décision de l'administrateur de la plateforme. L'accès aux données opérationnelles et comptables est actuellement verrouillé.
                </p>

                <div className="border-t border-border pt-4">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                    >
                        <ArrowLeft className="size-3.5" />
                        <span>Retour à l'accueil</span>
                    </Link>
                </div>
            </div>
        </div>
    );
}

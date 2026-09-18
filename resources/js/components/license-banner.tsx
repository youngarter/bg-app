import { AlertOctagon, AlertTriangle } from 'lucide-react';
import { useCanWrite } from '@/hooks/use-can-write';
import { formatDate } from '@/lib/formatters';

export function LicenseBanner() {
    const { license, isGrace, isReadOnly } = useCanWrite();

    if (!license || (!isGrace && !isReadOnly)) {
        return null;
    }

    if (isGrace) {
        return (
            <div className="flex items-start gap-3 border-b border-warn/30 bg-warn-surface px-4 py-2.5 text-xs text-warn">
                <AlertTriangle className="size-4 shrink-0 mt-0.5 text-warn" />
                <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <span className="font-semibold">Licence en période de grâce</span>
                    <span className="text-warn/90">
                        L'écriture reste permise jusqu'au {formatDate(license.grace_ends_on)}.
                        Passé ce délai, la résidence basculera en lecture seule.
                    </span>
                </div>
            </div>
        );
    }

    if (isReadOnly) {
        return (
            <div className="flex items-start gap-3 border-b border-crit/30 bg-crit-surface px-4 py-2.5 text-xs text-crit">
                <AlertOctagon className="size-4 shrink-0 mt-0.5 text-crit" />
                <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <span className="font-semibold">Licence en lecture seule</span>
                    <span className="text-crit/90">
                        La saisie et les écritures sont suspendues. La consultation, l'export et l'impression des états restent entièrement disponibles.
                    </span>
                </div>
            </div>
        );
    }

    return null;
}

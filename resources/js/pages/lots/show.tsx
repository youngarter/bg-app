import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Clock,
    History,
    RefreshCw,
    UserPlus,
} from 'lucide-react';
import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useCanWrite } from '@/hooks/use-can-write';
import {
    formatDate,
    formatInteger,
    formatOwnershipQuotePart,
    formatTantiemesPercentage,
} from '@/lib/formatters';

interface OwnershipItem {
    id: number;
    owner_id: number;
    owner_name: string;
    owner_type?: string;
    nature: string;
    nature_label: string;
    quote_part: number;
    started_on: string;
    ended_on: string | null;
    is_active: boolean;
    document_path: string | null;
}

interface LotShowProps {
    lot: {
        id: number;
        reference: string;
        type: string;
        type_label: string;
        batiment: string | null;
        etage: string | null;
        superficie: number | null;
        tantiemes: number;
        account: { id: number; code: string } | null;
        ownerships: OwnershipItem[];
    };
    availableOwners: Array<{ id: number; label: string; type: string }>;
    ownershipNatures: Array<{ value: string; label: string }>;
    lotTypes?: Array<{ value: string; label: string }>;
    residenceTotalTantiemes: number;
}

export default function LotShow({
    lot,
    availableOwners,
    ownershipNatures,
    residenceTotalTantiemes,
}: LotShowProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tableau de bord', href: '/dashboard' },
            { title: 'Lots & Tantièmes', href: '/lots' },
            { title: `Lot ${lot.reference}`, href: `/lots/${lot.id}` },
        ],
    });

    const { canWrite, disabledReason } = useCanWrite();
    const [isAttachOpen, setIsAttachOpen] = useState(false);
    const [isMutateOpen, setIsMutateOpen] = useState(false);

    // Formulaire d'attachement de détention datée
    const attachForm = useForm({
        owner_id: availableOwners[0]?.id || '',
        nature: ownershipNatures[0]?.value || 'pleine_propriete',
        quote_part: '10000', // 100% par défaut
        started_on: new Date().toISOString().split('T')[0],
        ended_on: '',
        document_path: '',
    });

    // Formulaire de mutation datée
    const mutateForm = useForm({
        buyer_id: availableOwners[0]?.id || '',
        nature: ownershipNatures[0]?.value || 'pleine_propriete',
        quote_part: '10000',
        effective_date: new Date().toISOString().split('T')[0],
        prix: '',
        document_path: '',
    });

    const handleAttach = (e: React.FormEvent) => {
        e.preventDefault();

        if (!canWrite) {
            return;
        }

        attachForm.post(`/lots/${lot.id}/ownerships`, {
            onSuccess: () => {
                setIsAttachOpen(false);
                attachForm.reset();
            },
        });
    };

    const handleMutate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!canWrite) {
            return;
        }

        mutateForm.post(`/lots/${lot.id}/mutate`, {
            onSuccess: () => {
                setIsMutateOpen(false);
                mutateForm.reset();
            },
        });
    };

    return (
        <>
            <Head title={`Lot ${lot.reference}`} />

            <div className="flex w-full flex-1 flex-col gap-6 p-4 sm:p-6">
                {/* Navigation retour & Actions */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/lots"
                            className="inline-flex size-8 items-center justify-center rounded-md border border-border bg-card text-foreground transition-colors hover:bg-muted"
                        >
                            <ArrowLeft className="size-4" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="font-display text-2xl font-bold text-foreground">
                                    Lot {lot.reference}
                                </h1>
                                <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground capitalize">
                                    {lot.type_label}
                                </span>
                            </div>
                            <p className="mt-0.5 font-mono text-xs text-muted-foreground">
                                Compte auxiliaire : {lot.account?.code || '—'}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Bouton rattacher un copropriétaire */}
                        <Dialog
                            open={isAttachOpen}
                            onOpenChange={setIsAttachOpen}
                        >
                            <DialogTrigger asChild>
                                <button
                                    type="button"
                                    disabled={
                                        !canWrite ||
                                        availableOwners.length === 0
                                    }
                                    title={
                                        disabledReason ||
                                        (availableOwners.length === 0
                                            ? "Créez d'abord un copropriétaire"
                                            : undefined)
                                    }
                                    className={`inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors ${
                                        canWrite && availableOwners.length > 0
                                            ? 'text-foreground hover:bg-muted'
                                            : 'cursor-not-allowed text-muted-foreground opacity-60'
                                    }`}
                                >
                                    <UserPlus className="size-3.5 text-primary" />
                                    <span>Ajouter une détention</span>
                                </button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-md">
                                <form onSubmit={handleAttach}>
                                    <DialogHeader>
                                        <DialogTitle className="font-display text-base">
                                            Nouvelle détention sur le lot{' '}
                                            {lot.reference}
                                        </DialogTitle>
                                        <DialogDescription className="text-xs text-muted-foreground">
                                            Rattachement daté d'un
                                            copropriétaire avec nature juridique
                                            et quote-part.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div className="grid gap-3.5 py-4 text-xs">
                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Copropriétaire *
                                            </label>
                                            <select
                                                value={attachForm.data.owner_id}
                                                onChange={(e) =>
                                                    attachForm.setData(
                                                        'owner_id',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            >
                                                {availableOwners.map(
                                                    (owner) => (
                                                        <option
                                                            key={owner.id}
                                                            value={owner.id}
                                                        >
                                                            {owner.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            {attachForm.errors.owner_id && (
                                                <p className="mt-1 text-[11px] text-crit">
                                                    {attachForm.errors.owner_id}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Nature de détention *
                                                </label>
                                                <select
                                                    value={
                                                        attachForm.data.nature
                                                    }
                                                    onChange={(e) =>
                                                        attachForm.setData(
                                                            'nature',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                >
                                                    {ownershipNatures.map(
                                                        (n) => (
                                                            <option
                                                                key={n.value}
                                                                value={n.value}
                                                            >
                                                                {n.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </div>

                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Quote-part (points de base)
                                                    *
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="10000"
                                                    value={
                                                        attachForm.data
                                                            .quote_part
                                                    }
                                                    onChange={(e) =>
                                                        attachForm.setData(
                                                            'quote_part',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                    placeholder="10000 = 100 %"
                                                />
                                                <span className="mt-0.5 block text-[10px] text-muted-foreground">
                                                    Soit{' '}
                                                    {formatOwnershipQuotePart(
                                                        parseInt(
                                                            attachForm.data
                                                                .quote_part,
                                                        ) || 0,
                                                    )}{' '}
                                                    du lot
                                                </span>
                                                {attachForm.errors
                                                    .quote_part && (
                                                    <p className="mt-1 text-[11px] text-crit">
                                                        {
                                                            attachForm.errors
                                                                .quote_part
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Date d'effet (début) *
                                                </label>
                                                <input
                                                    type="date"
                                                    required
                                                    value={
                                                        attachForm.data
                                                            .started_on
                                                    }
                                                    onChange={(e) =>
                                                        attachForm.setData(
                                                            'started_on',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                                {attachForm.errors
                                                    .started_on && (
                                                    <p className="mt-1 text-[11px] text-crit">
                                                        {
                                                            attachForm.errors
                                                                .started_on
                                                        }
                                                    </p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Date de fin (optionnelle)
                                                </label>
                                                <input
                                                    type="date"
                                                    value={
                                                        attachForm.data.ended_on
                                                    }
                                                    onChange={(e) =>
                                                        attachForm.setData(
                                                            'ended_on',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                                {attachForm.errors.ended_on && (
                                                    <p className="mt-1 text-[11px] text-crit">
                                                        {
                                                            attachForm.errors
                                                                .ended_on
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setIsAttachOpen(false)
                                            }
                                            className="rounded-md border border-border px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                                        >
                                            Annuler
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={attachForm.processing}
                                            className="rounded-md bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                                        >
                                            Enregistrer la détention
                                        </button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>

                        {/* Bouton mutation datée */}
                        <Dialog
                            open={isMutateOpen}
                            onOpenChange={setIsMutateOpen}
                        >
                            <DialogTrigger asChild>
                                <button
                                    type="button"
                                    disabled={
                                        !canWrite ||
                                        availableOwners.length === 0
                                    }
                                    title={disabledReason}
                                    className={`inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground shadow-xs transition-colors ${
                                        canWrite && availableOwners.length > 0
                                            ? 'hover:bg-primary/90'
                                            : 'cursor-not-allowed opacity-60'
                                    }`}
                                >
                                    <RefreshCw className="size-3.5" />
                                    <span>Enregistrer une mutation</span>
                                </button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-md">
                                <form onSubmit={handleMutate}>
                                    <DialogHeader>
                                        <DialogTitle className="font-display text-base">
                                            Mutation du lot {lot.reference}
                                        </DialogTitle>
                                        <DialogDescription className="text-xs text-muted-foreground">
                                            Clôture automatique de la détention
                                            précédente et ouverture de la
                                            nouvelle détention à la date
                                            d'effet.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div className="grid gap-3.5 py-4 text-xs">
                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Nouveau copropriétaire acquéreur
                                                *
                                            </label>
                                            <select
                                                value={mutateForm.data.buyer_id}
                                                onChange={(e) =>
                                                    mutateForm.setData(
                                                        'buyer_id',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            >
                                                {availableOwners.map(
                                                    (owner) => (
                                                        <option
                                                            key={owner.id}
                                                            value={owner.id}
                                                        >
                                                            {owner.label}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            {mutateForm.errors.buyer_id && (
                                                <p className="mt-1 text-[11px] text-crit">
                                                    {mutateForm.errors.buyer_id}
                                                </p>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Date d'effet *
                                                </label>
                                                <input
                                                    type="date"
                                                    required
                                                    value={
                                                        mutateForm.data
                                                            .effective_date
                                                    }
                                                    onChange={(e) =>
                                                        mutateForm.setData(
                                                            'effective_date',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                                {mutateForm.errors
                                                    .effective_date && (
                                                    <p className="mt-1 text-[11px] text-crit">
                                                        {
                                                            mutateForm.errors
                                                                .effective_date
                                                        }
                                                    </p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Nature de détention *
                                                </label>
                                                <select
                                                    value={
                                                        mutateForm.data.nature
                                                    }
                                                    onChange={(e) =>
                                                        mutateForm.setData(
                                                            'nature',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                >
                                                    {ownershipNatures.map(
                                                        (n) => (
                                                            <option
                                                                key={n.value}
                                                                value={n.value}
                                                            >
                                                                {n.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setIsMutateOpen(false)
                                            }
                                            className="rounded-md border border-border px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                                        >
                                            Annuler
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={mutateForm.processing}
                                            className="rounded-md bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                                        >
                                            Enregistrer la mutation
                                        </button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                {/* Caractéristiques du lot */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <span className="mb-1 block text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Tantièmes
                        </span>
                        <div className="font-display text-2xl font-bold text-foreground">
                            {formatInteger(lot.tantiemes)}
                        </div>
                        <span className="mt-0.5 block font-mono text-xs text-muted-foreground">
                            {formatTantiemesPercentage(
                                lot.tantiemes,
                                residenceTotalTantiemes,
                            )}{' '}
                            de la copropriété
                        </span>
                    </div>

                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <span className="mb-1 block text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Localisation
                        </span>
                        <div className="font-display text-base font-semibold text-foreground">
                            {lot.batiment || 'Non spécifié'}
                        </div>
                        <span className="mt-0.5 block text-xs text-muted-foreground">
                            {lot.etage
                                ? `Étage : ${lot.etage}`
                                : 'Étage non spécifié'}
                        </span>
                    </div>

                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <span className="mb-1 block text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Superficie
                        </span>
                        <div className="font-display font-mono text-xl font-semibold text-foreground">
                            {lot.superficie ? `${lot.superficie} m²` : '—'}
                        </div>
                        <span className="mt-0.5 block text-xs text-muted-foreground">
                            Superficie déclarée
                        </span>
                    </div>

                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <span className="mb-1 block text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Compte de lot
                        </span>
                        <div className="font-mono text-base font-bold text-foreground">
                            {lot.account?.code || '—'}
                        </div>
                        <span className="mt-0.5 block text-xs text-muted-foreground">
                            Compte auxiliaire unique
                        </span>
                    </div>
                </div>

                {/* Chronologie des détentions datées */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <div className="flex items-center justify-between border-b border-border p-4">
                        <div className="flex items-center gap-2">
                            <History className="size-4 text-primary" />
                            <h2 className="font-display text-base font-semibold text-foreground">
                                Chronologie des détentions
                            </h2>
                        </div>
                        <span className="text-xs font-medium text-muted-foreground">
                            {lot.ownerships.length} période(s) enregistrée(s)
                        </span>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-foreground">
                            <thead className="border-b border-border bg-muted/40 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">
                                        Copropriétaire
                                    </th>
                                    <th className="px-4 py-3">
                                        Nature juridique
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Quote-part du lot
                                    </th>
                                    <th className="px-4 py-3">
                                        Période de détention
                                    </th>
                                    <th className="px-4 py-3">Statut</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {lot.ownerships.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Aucune détention n'est encore
                                            enregistrée pour ce lot.
                                        </td>
                                    </tr>
                                ) : (
                                    lot.ownerships.map((o) => (
                                        <tr
                                            key={o.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-semibold text-foreground">
                                                <Link
                                                    href={`/owners/${o.owner_id}`}
                                                    className="hover:text-primary hover:underline"
                                                >
                                                    {o.owner_name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-medium text-foreground">
                                                    {o.nature_label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right font-mono font-semibold text-foreground">
                                                {formatOwnershipQuotePart(
                                                    o.quote_part,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-muted-foreground">
                                                {formatDate(o.started_on)} →{' '}
                                                {o.ended_on
                                                    ? formatDate(o.ended_on)
                                                    : 'en cours'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {o.is_active ? (
                                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-ok/20 bg-ok-surface px-2 py-0.5 text-[11px] font-semibold text-ok">
                                                        <CheckCircle2 className="size-3 shrink-0" />
                                                        <span>Actif</span>
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                                        <Clock className="size-3 shrink-0" />
                                                        <span>Historique</span>
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

LotShow.layout = {
    breadcrumbs: [{ title: 'Lots & Tantièmes', href: '/lots' }],
};

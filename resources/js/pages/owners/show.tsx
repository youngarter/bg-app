import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { Building2, Edit3, Phone, User } from 'lucide-react';
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
} from '@/lib/formatters';

interface OwnershipItem {
    id: number;
    lot_id: number;
    lot_reference: string;
    lot_type: string;
    lot_tantiemes: number;
    nature: string;
    nature_label: string;
    quote_part: number;
    started_on: string;
    ended_on: string | null;
    is_active: boolean;
}

interface OwnerDetails {
    id: number;
    type: string;
    type_label: string;
    nom: string | null;
    prenom: string | null;
    raison_sociale: string | null;
    display_name: string;
    cin: string | null;
    ice: string | null;
    telephone: string | null;
    email: string | null;
    adresse: string | null;
    has_user_account: boolean;
    ownerships: OwnershipItem[];
}

interface OwnerShowProps {
    owner: OwnerDetails;
    ownerTypes: Array<{ value: string; label: string }>;
}

export default function OwnerShow({ owner, ownerTypes }: OwnerShowProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tableau de bord', href: '/dashboard' },
            { title: 'Copropriétaires', href: '/owners' },
            { title: owner.display_name, href: `/owners/${owner.id}` },
        ],
    });

    const { canWrite, disabledReason } = useCanWrite();
    const [isEditOpen, setIsEditOpen] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        type: owner.type,
        nom: owner.nom || '',
        prenom: owner.prenom || '',
        raison_sociale: owner.raison_sociale || '',
        cin: owner.cin || '',
        ice: owner.ice || '',
        telephone: owner.telephone || '',
        email: owner.email || '',
        adresse: owner.adresse || '',
    });

    const isPhysique = data.type === 'personne_physique';

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!canWrite) {
            return;
        }

        put(`/owners/${owner.id}`, {
            onSuccess: () => setIsEditOpen(false),
        });
    };

    const activeOwnerships = owner.ownerships.filter((o) => o.is_active);
    const pastOwnerships = owner.ownerships.filter((o) => !o.is_active);

    return (
        <>
            <Head title={`Copropriétaire — ${owner.display_name}`} />

            <div className="flex w-full flex-1 flex-col gap-5 p-4 sm:p-6">
                {/* En-tête */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="font-display text-2xl font-bold text-foreground">
                                {owner.display_name}
                            </h1>
                            <span className="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground capitalize">
                                {owner.type_label}
                            </span>
                        </div>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Fiche d'identification juridique et historique des
                            détentions.
                        </p>
                    </div>

                    <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                        <DialogTrigger asChild>
                            <button
                                type="button"
                                disabled={!canWrite}
                                title={disabledReason}
                                className={`inline-flex items-center gap-2 rounded-md px-3.5 py-2 text-xs font-semibold shadow-xs transition-colors ${
                                    canWrite
                                        ? 'border border-border bg-card text-foreground hover:bg-muted'
                                        : 'cursor-not-allowed border border-border bg-muted text-muted-foreground opacity-70'
                                }`}
                            >
                                <Edit3 className="size-3.5" />
                                <span>Modifier la fiche</span>
                            </button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-md">
                            <form onSubmit={handleUpdate}>
                                <DialogHeader>
                                    <DialogTitle className="font-display text-lg">
                                        Modifier les informations
                                    </DialogTitle>
                                    <DialogDescription className="text-xs text-muted-foreground">
                                        Mettez à jour les données signalétiques
                                        du copropriétaire.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-3.5 py-4 text-xs">
                                    <div>
                                        <label className="mb-1 block font-semibold text-foreground">
                                            Type juridique *
                                        </label>
                                        <select
                                            value={data.type}
                                            onChange={(e) =>
                                                setData('type', e.target.value)
                                            }
                                            className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                        >
                                            {ownerTypes.map((t) => (
                                                <option
                                                    key={t.value}
                                                    value={t.value}
                                                >
                                                    {t.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.type && (
                                            <p className="mt-1 text-[11px] text-crit">
                                                {errors.type}
                                            </p>
                                        )}
                                    </div>

                                    {isPhysique ? (
                                        <>
                                            <div className="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label className="mb-1 block font-semibold text-foreground">
                                                        Nom *
                                                    </label>
                                                    <input
                                                        type="text"
                                                        required
                                                        value={data.nom}
                                                        onChange={(e) =>
                                                            setData(
                                                                'nom',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                    />
                                                    {errors.nom && (
                                                        <p className="mt-1 text-[11px] text-crit">
                                                            {errors.nom}
                                                        </p>
                                                    )}
                                                </div>

                                                <div>
                                                    <label className="mb-1 block font-semibold text-foreground">
                                                        Prénom
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={data.prenom}
                                                        onChange={(e) =>
                                                            setData(
                                                                'prenom',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                    />
                                                </div>
                                            </div>

                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    CIN
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.cin}
                                                    onChange={(e) =>
                                                        setData(
                                                            'cin',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Raison sociale *
                                                </label>
                                                <input
                                                    type="text"
                                                    required
                                                    value={data.raison_sociale}
                                                    onChange={(e) =>
                                                        setData(
                                                            'raison_sociale',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                                {errors.raison_sociale && (
                                                    <p className="mt-1 text-[11px] text-crit">
                                                        {errors.raison_sociale}
                                                    </p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    ICE
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.ice}
                                                    onChange={(e) =>
                                                        setData(
                                                            'ice',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                            </div>
                                        </>
                                    )}

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Téléphone
                                            </label>
                                            <input
                                                type="tel"
                                                value={data.telephone}
                                                onChange={(e) =>
                                                    setData(
                                                        'telephone',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                        </div>

                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Email
                                            </label>
                                            <input
                                                type="email"
                                                value={data.email}
                                                onChange={(e) =>
                                                    setData(
                                                        'email',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                            {errors.email && (
                                                <p className="mt-1 text-[11px] text-crit">
                                                    {errors.email}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="mb-1 block font-semibold text-foreground">
                                            Adresse postale
                                        </label>
                                        <input
                                            type="text"
                                            value={data.adresse}
                                            onChange={(e) =>
                                                setData(
                                                    'adresse',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                        />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <button
                                        type="button"
                                        onClick={() => setIsEditOpen(false)}
                                        className="rounded-md border border-border px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                                    >
                                        Enregistrer les modifications
                                    </button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Grille d'information */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <div className="mb-3 flex items-center gap-2">
                            <User className="size-4 text-primary" />
                            <h3 className="font-display text-xs font-semibold text-foreground">
                                État civil / Immatriculation
                            </h3>
                        </div>
                        <div className="space-y-2 text-xs">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Type :
                                </span>
                                <span className="font-medium text-foreground capitalize">
                                    {owner.type_label}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    {isPhysique ? 'CIN :' : 'ICE :'}
                                </span>
                                <span className="font-mono font-medium text-foreground">
                                    {owner.cin || owner.ice || '—'}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Compte d'accès :
                                </span>
                                <span
                                    className={
                                        owner.has_user_account
                                            ? 'font-semibold text-ok'
                                            : 'text-muted-foreground italic'
                                    }
                                >
                                    {owner.has_user_account
                                        ? 'Compte actif'
                                        : 'Sans accès portail'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <div className="mb-3 flex items-center gap-2">
                            <Phone className="size-4 text-primary" />
                            <h3 className="font-display text-xs font-semibold text-foreground">
                                Coordonnées de contact
                            </h3>
                        </div>
                        <div className="space-y-2 text-xs">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Téléphone :
                                </span>
                                <span className="font-mono font-medium text-foreground">
                                    {owner.telephone || '—'}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Email :
                                </span>
                                <span className="font-medium text-foreground">
                                    {owner.email || '—'}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Adresse :
                                </span>
                                <span
                                    className="max-w-[180px] truncate text-right font-medium text-foreground"
                                    title={owner.adresse || ''}
                                >
                                    {owner.adresse || '—'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-card p-4 shadow-xs">
                        <div className="mb-3 flex items-center gap-2">
                            <Building2 className="size-4 text-primary" />
                            <h3 className="font-display text-xs font-semibold text-foreground">
                                Synthèse des détentions
                            </h3>
                        </div>
                        <div className="space-y-2 text-xs">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Lots actifs :
                                </span>
                                <span className="font-mono font-semibold text-foreground">
                                    {activeOwnerships.length}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Total tantièmes gérés :
                                </span>
                                <span className="font-mono font-semibold text-foreground">
                                    {formatInteger(
                                        activeOwnerships.reduce(
                                            (acc, o) => acc + o.lot_tantiemes,
                                            0,
                                        ),
                                    )}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Anciennes détentions :
                                </span>
                                <span className="font-mono text-muted-foreground">
                                    {pastOwnerships.length}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Section des lots actifs */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <div className="flex items-center justify-between border-b border-border bg-muted/40 px-4 py-3">
                        <div>
                            <h2 className="font-display text-xs font-bold tracking-wider text-foreground uppercase">
                                Lots actuellement détenus (
                                {activeOwnerships.length})
                            </h2>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-foreground">
                            <thead className="border-b border-border bg-muted/20 text-[11px] font-semibold text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-2.5">Lot</th>
                                    <th className="px-4 py-2.5">Type de lot</th>
                                    <th className="px-4 py-2.5">
                                        Nature du droit
                                    </th>
                                    <th className="px-4 py-2.5 text-right">
                                        Quote-part
                                    </th>
                                    <th className="px-4 py-2.5 text-right">
                                        Tantièmes lot
                                    </th>
                                    <th className="px-4 py-2.5">
                                        Date d'effet
                                    </th>
                                    <th className="px-4 py-2.5 text-right">
                                        Fiche lot
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {activeOwnerships.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            Ce copropriétaire ne détient
                                            actuellement aucun lot actif.
                                        </td>
                                    </tr>
                                ) : (
                                    activeOwnerships.map((o) => (
                                        <tr
                                            key={o.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-2.5 font-semibold text-foreground">
                                                <Link
                                                    href={`/lots/${o.lot_id}`}
                                                    className="hover:text-primary hover:underline"
                                                >
                                                    Lot {o.lot_reference}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-2.5 text-muted-foreground capitalize">
                                                {o.lot_type}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground">
                                                    {o.nature_label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-mono font-semibold text-foreground">
                                                {formatOwnershipQuotePart(
                                                    o.quote_part,
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-mono text-muted-foreground">
                                                {formatInteger(o.lot_tantiemes)}
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-[11px] text-muted-foreground">
                                                Depuis le{' '}
                                                {formatDate(o.started_on)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Link
                                                    href={`/lots/${o.lot_id}`}
                                                    className="text-xs font-semibold text-primary hover:underline"
                                                >
                                                    Consulter →
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Historique des anciennes détentions */}
                {pastOwnerships.length > 0 && (
                    <div className="overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                        <div className="border-b border-border bg-muted/40 px-4 py-3">
                            <h2 className="font-display text-xs font-bold tracking-wider text-muted-foreground uppercase">
                                Historique des détentions closes (
                                {pastOwnerships.length})
                            </h2>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs text-muted-foreground">
                                <thead className="border-b border-border bg-muted/20 text-[11px] font-semibold uppercase">
                                    <tr>
                                        <th className="px-4 py-2.5">Lot</th>
                                        <th className="px-4 py-2.5">Nature</th>
                                        <th className="px-4 py-2.5 text-right">
                                            Quote-part
                                        </th>
                                        <th className="px-4 py-2.5">Début</th>
                                        <th className="px-4 py-2.5">Fin</th>
                                        <th className="px-4 py-2.5 text-right">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {pastOwnerships.map((o) => (
                                        <tr
                                            key={o.id}
                                            className="hover:bg-muted/20"
                                        >
                                            <td className="px-4 py-2.5 font-medium text-foreground">
                                                Lot {o.lot_reference}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                {o.nature_label}
                                            </td>
                                            <td className="px-4 py-2.5 text-right font-mono">
                                                {formatOwnershipQuotePart(
                                                    o.quote_part,
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-[11px]">
                                                {formatDate(o.started_on)}
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-[11px]">
                                                {o.ended_on
                                                    ? formatDate(o.ended_on)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-2.5 text-right">
                                                <Link
                                                    href={`/lots/${o.lot_id}`}
                                                    className="text-xs text-primary hover:underline"
                                                >
                                                    Voir lot
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

OwnerShow.layout = {
    breadcrumbs: [{ title: 'Copropriétaires', href: '/owners' }],
};

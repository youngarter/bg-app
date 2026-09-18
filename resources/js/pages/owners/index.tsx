import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/react';
import { Filter, Plus, Search, UserCheck } from 'lucide-react';
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

interface OwnerItem {
    id: number;
    type: string;
    nom: string | null;
    prenom: string | null;
    raison_sociale: string | null;
    cin: string | null;
    ice: string | null;
    telephone: string | null;
    email: string | null;
    user_id: number | null;
    ownerships: Array<{
        id: number;
        lot: { id: number; reference: string; tantiemes: number } | null;
    }>;
}

interface OwnersIndexProps {
    owners: {
        data: OwnerItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search: string;
        type: string;
    };
    ownerTypes: Array<{ value: string; label: string }>;
}

export default function OwnersIndex({
    owners,
    filters,
    ownerTypes,
}: OwnersIndexProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tableau de bord', href: '/dashboard' },
            { title: 'Copropriétaires', href: '/owners' },
        ],
    });

    const { canWrite, disabledReason } = useCanWrite();
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const { data, setData, post, processing, errors, reset } = useForm({
        type: ownerTypes[0]?.value || 'personne_physique',
        nom: '',
        prenom: '',
        raison_sociale: '',
        cin: '',
        ice: '',
        telephone: '',
        email: '',
        adresse: '',
    });

    const isPhysique = data.type === 'personne_physique';

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/owners',
            {
                ...filters,
                search: searchTerm,
            },
            { preserveState: true },
        );
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();

        if (!canWrite) {
            return;
        }

        post('/owners', {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Copropriétaires" />

            <div className="flex w-full flex-1 flex-col gap-5 p-4 sm:p-6">
                {/* En-tête */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="font-display text-2xl font-bold text-foreground">
                            Registre des copropriétaires
                        </h1>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Fiches d'identité, coordonnées et détention des lots
                            de la copropriété.
                        </p>
                    </div>

                    <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                        <DialogTrigger asChild>
                            <button
                                type="button"
                                disabled={!canWrite}
                                title={disabledReason}
                                className={`inline-flex items-center gap-2 rounded-md px-3.5 py-2 text-xs font-semibold shadow-xs transition-colors ${
                                    canWrite
                                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'cursor-not-allowed bg-muted text-muted-foreground opacity-70'
                                }`}
                            >
                                <Plus className="size-4" />
                                <span>Ajouter un copropriétaire</span>
                            </button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-md">
                            <form onSubmit={handleCreate}>
                                <DialogHeader>
                                    <DialogTitle className="font-display text-lg">
                                        Nouveau copropriétaire
                                    </DialogTitle>
                                    <DialogDescription className="text-xs text-muted-foreground">
                                        Saisissez l'identité juridique.
                                        L'association à un compte de connexion
                                        est facultative.
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
                                                        placeholder="ex: El Idrissi"
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
                                                        placeholder="ex: Mohammed"
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
                                                    placeholder="ex: BE123456"
                                                    className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                                />
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            <div>
                                                <label className="mb-1 block font-semibold text-foreground">
                                                    Raison sociale / Désignation
                                                    *
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
                                                    placeholder="ex: Société Immobilière Atlas SARL"
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
                                                    ICE (Identifiant Commun de
                                                    l'Entreprise)
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
                                                    placeholder="ex: 001234567000089"
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
                                                placeholder="ex: 0661234567"
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
                                                placeholder="ex: contact@exemple.ma"
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
                                            placeholder="Adresse de correspondance"
                                            className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                        />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <button
                                        type="button"
                                        onClick={() => setIsCreateOpen(false)}
                                        className="rounded-md border border-border px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md bg-primary px-3.5 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                                    >
                                        Enregistrer
                                    </button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Filtres & Recherche */}
                <div className="flex flex-col items-center gap-3 sm:flex-row">
                    <form
                        onSubmit={handleSearch}
                        className="relative w-full flex-1"
                    >
                        <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <input
                            type="text"
                            placeholder="Rechercher par nom, prénom, raison sociale, CIN, email..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full rounded-md border border-input bg-card py-1.5 pr-3 pl-9 text-xs text-foreground outline-none placeholder:text-muted-foreground focus:ring-1 focus:ring-primary"
                        />
                    </form>

                    <div className="flex w-full items-center gap-2 sm:w-auto">
                        <Filter className="size-3.5 shrink-0 text-muted-foreground" />
                        <select
                            value={filters.type}
                            onChange={(e) =>
                                router.get(
                                    '/owners',
                                    { ...filters, type: e.target.value },
                                    { preserveState: true },
                                )
                            }
                            className="rounded-md border border-input bg-card px-2.5 py-1.5 text-xs text-foreground outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Tous les types</option>
                            {ownerTypes.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Conteneur overflow-x pour le tableau (règle js.md:17) */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs text-foreground">
                            <thead className="border-b border-border bg-muted/50 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Identité</th>
                                    <th className="px-4 py-3">Type</th>
                                    <th className="px-4 py-3">CIN / ICE</th>
                                    <th className="px-4 py-3">Téléphone</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Lots détenus</th>
                                    <th className="px-4 py-3">
                                        Compte portail
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {owners.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Aucun copropriétaire enregistré ou
                                            correspondant aux critères.
                                        </td>
                                    </tr>
                                ) : (
                                    owners.data.map((owner) => {
                                        const name =
                                            owner.raison_sociale ||
                                            `${owner.prenom || ''} ${owner.nom || ''}`.trim();
                                        const activeLots =
                                            owner.ownerships
                                                ?.map((o) => o.lot?.reference)
                                                .filter(Boolean) || [];

                                        return (
                                            <tr
                                                key={owner.id}
                                                className="transition-colors hover:bg-muted/40"
                                            >
                                                <td className="px-4 py-3 font-semibold text-foreground">
                                                    <Link
                                                        href={`/owners/${owner.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {name}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground capitalize">
                                                    {owner.type.replace(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 font-mono text-[11px] text-muted-foreground">
                                                    {owner.cin ||
                                                        owner.ice ||
                                                        '—'}
                                                </td>
                                                <td className="px-4 py-3 font-mono text-muted-foreground">
                                                    {owner.telephone || '—'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {owner.email || '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {activeLots.length > 0 ? (
                                                        <div className="flex flex-wrap gap-1">
                                                            {activeLots.map(
                                                                (ref, i) => (
                                                                    <span
                                                                        key={i}
                                                                        className="inline-flex items-center rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground"
                                                                    >
                                                                        Lot{' '}
                                                                        {ref}
                                                                    </span>
                                                                ),
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground italic">
                                                            Aucun lot actif
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {owner.user_id ? (
                                                        <span className="inline-flex items-center gap-1 text-[11px] font-medium text-ok">
                                                            <UserCheck className="size-3" />
                                                            <span>Actif</span>
                                                        </span>
                                                    ) : (
                                                        <span className="text-[11px] text-muted-foreground italic">
                                                            Non associé
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <Link
                                                        href={`/owners/${owner.id}`}
                                                        className="text-xs font-semibold text-primary hover:underline"
                                                    >
                                                        Fiche →
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {owners.total > 15 && (
                        <div className="flex items-center justify-between border-t border-border bg-muted/20 px-4 py-3 text-xs">
                            <span className="text-muted-foreground">
                                Affichage de {owners.data.length} sur{' '}
                                {owners.total} copropriétaires
                            </span>
                            <div className="flex items-center gap-1">
                                {owners.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        type="button"
                                        disabled={!link.url || link.active}
                                        onClick={() =>
                                            link.url &&
                                            router.get(
                                                link.url,
                                                {},
                                                { preserveState: true },
                                            )
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        className={`rounded px-2.5 py-1 text-xs transition-colors ${
                                            link.active
                                                ? 'bg-primary font-semibold text-primary-foreground'
                                                : link.url
                                                  ? 'text-foreground hover:bg-muted'
                                                  : 'cursor-not-allowed text-muted-foreground/50'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

OwnersIndex.layout = {
    breadcrumbs: [{ title: 'Copropriétaires', href: '/owners' }],
};

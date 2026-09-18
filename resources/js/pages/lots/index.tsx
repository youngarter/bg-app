import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/react';
import { Filter, Plus, Search } from 'lucide-react';
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
import { formatInteger, formatTantiemesPercentage } from '@/lib/formatters';

interface LotItem {
    id: number;
    reference: string;
    type: string;
    batiment: string | null;
    etage: string | null;
    superficie: number | null;
    tantiemes: number;
    account: { id: number; code: string } | null;
    ownerships: Array<{
        id: number;
        owner: {
            id: number;
            nom: string;
            prenom: string;
            raison_sociale: string;
        } | null;
    }>;
}

interface LotsIndexProps {
    lots: {
        data: LotItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search: string;
        type: string;
        sort: string;
        direction: string;
    };
    lotTypes: Array<{ value: string; label: string }>;
    residenceTotalTantiemes: number;
}

export default function LotsIndex({
    lots,
    filters,
    lotTypes,
    residenceTotalTantiemes,
}: LotsIndexProps) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tableau de bord', href: '/dashboard' },
            { title: 'Lots & Tantièmes', href: '/lots' },
        ],
    });

    const { canWrite, disabledReason } = useCanWrite();
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const { data, setData, post, processing, errors, reset } = useForm({
        reference: '',
        type: lotTypes[0]?.value || 'appartement',
        batiment: '',
        etage: '',
        superficie: '',
        tantiemes: '',
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/lots',
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

        post('/lots', {
            onSuccess: () => {
                setIsCreateOpen(false);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Lots et tantièmes" />

            <div className="flex w-full flex-1 flex-col gap-5 p-4 sm:p-6">
                {/* En-tête de page */}
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="font-display text-2xl font-bold text-foreground">
                            Lots de copropriété
                        </h1>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Gestion des lots, tantièmes de répartition et
                            comptes auxiliaires.
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
                                <span>Ajouter un lot</span>
                            </button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-md">
                            <form onSubmit={handleCreate}>
                                <DialogHeader>
                                    <DialogTitle className="font-display text-lg">
                                        Nouveau lot de copropriété
                                    </DialogTitle>
                                    <DialogDescription className="text-xs text-muted-foreground">
                                        Chaque lot créé recevra automatiquement
                                        son compte auxiliaire dédié.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4 text-xs">
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Numéro / Référence *
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                placeholder="ex: A12, C04"
                                                value={data.reference}
                                                onChange={(e) =>
                                                    setData(
                                                        'reference',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                            {errors.reference && (
                                                <p className="mt-1 text-[11px] text-crit">
                                                    {errors.reference}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Type de lot *
                                            </label>
                                            <select
                                                value={data.type}
                                                onChange={(e) =>
                                                    setData(
                                                        'type',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            >
                                                {lotTypes.map((t) => (
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
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Bâtiment
                                            </label>
                                            <input
                                                type="text"
                                                placeholder="ex: Bâtiment A"
                                                value={data.batiment}
                                                onChange={(e) =>
                                                    setData(
                                                        'batiment',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                        </div>

                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Étage
                                            </label>
                                            <input
                                                type="text"
                                                placeholder="ex: 2ème étage"
                                                value={data.etage}
                                                onChange={(e) =>
                                                    setData(
                                                        'etage',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Superficie (m²)
                                            </label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                placeholder="ex: 85.50"
                                                value={data.superficie}
                                                onChange={(e) =>
                                                    setData(
                                                        'superficie',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                        </div>

                                        <div>
                                            <label className="mb-1 block font-semibold text-foreground">
                                                Tantièmes *
                                            </label>
                                            <input
                                                type="number"
                                                required
                                                min="1"
                                                placeholder={`sur ${residenceTotalTantiemes}`}
                                                value={data.tantiemes}
                                                onChange={(e) =>
                                                    setData(
                                                        'tantiemes',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-input bg-card px-3 py-1.5 text-xs outline-none focus:ring-1 focus:ring-primary"
                                            />
                                            {errors.tantiemes && (
                                                <p className="mt-1 text-[11px] text-crit">
                                                    {errors.tantiemes}
                                                </p>
                                            )}
                                        </div>
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
                                        Enregistrer le lot
                                    </button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Barre de filtres et recherche */}
                <div className="flex flex-col items-center gap-3 sm:flex-row">
                    <form
                        onSubmit={handleSearch}
                        className="relative w-full flex-1"
                    >
                        <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <input
                            type="text"
                            placeholder="Rechercher par numéro de lot, bâtiment, étage..."
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
                                    '/lots',
                                    { ...filters, type: e.target.value },
                                    { preserveState: true },
                                )
                            }
                            className="rounded-md border border-input bg-card px-2.5 py-1.5 text-xs text-foreground outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Tous les types</option>
                            {lotTypes.map((t) => (
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
                                    <th className="px-4 py-3">Réf. Lot</th>
                                    <th className="px-4 py-3">Compte</th>
                                    <th className="px-4 py-3">Type</th>
                                    <th className="px-4 py-3">
                                        Bâtiment / Étage
                                    </th>
                                    <th className="px-4 py-3">Superficie</th>
                                    <th className="px-4 py-3 text-right">
                                        Tantièmes
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Quote-part résid.
                                    </th>
                                    <th className="px-4 py-3">
                                        Détenteur actuel
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {lots.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={9}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Aucun lot ne correspond aux critères
                                            de recherche.
                                        </td>
                                    </tr>
                                ) : (
                                    lots.data.map((lot) => {
                                        const currentOwner =
                                            lot.ownerships?.[0]?.owner;
                                        const ownerName = currentOwner
                                            ? currentOwner.raison_sociale ||
                                              `${currentOwner.prenom} ${currentOwner.nom}`
                                            : 'Sans détenteur actif';

                                        return (
                                            <tr
                                                key={lot.id}
                                                className="transition-colors hover:bg-muted/40"
                                            >
                                                <td className="px-4 py-3 font-semibold text-foreground">
                                                    <Link
                                                        href={`/lots/${lot.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {lot.reference}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3 font-mono text-[11px] text-muted-foreground">
                                                    {lot.account?.code || '—'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground capitalize">
                                                    {lot.type}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {lot.batiment || '—'}
                                                    {lot.etage
                                                        ? ` · ${lot.etage}`
                                                        : ''}
                                                </td>
                                                <td className="tabular px-4 py-3 font-mono text-muted-foreground">
                                                    {lot.superficie
                                                        ? `${lot.superficie} m²`
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right font-mono font-semibold text-foreground">
                                                    {formatInteger(
                                                        lot.tantiemes,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right font-mono text-muted-foreground">
                                                    {formatTantiemesPercentage(
                                                        lot.tantiemes,
                                                        residenceTotalTantiemes,
                                                    )}
                                                </td>
                                                <td className="max-w-[180px] truncate px-4 py-3">
                                                    <span
                                                        className={
                                                            currentOwner
                                                                ? 'text-foreground'
                                                                : 'text-muted-foreground italic'
                                                        }
                                                    >
                                                        {ownerName}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <Link
                                                        href={`/lots/${lot.id}`}
                                                        className="text-xs font-semibold text-primary hover:underline"
                                                    >
                                                        Détails →
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
                    {lots.total > 15 && (
                        <div className="flex items-center justify-between border-t border-border bg-muted/20 px-4 py-3 text-xs">
                            <span className="text-muted-foreground">
                                Affichage de {lots.data.length} sur {lots.total}{' '}
                                lots
                            </span>
                            <div className="flex items-center gap-1">
                                {lots.links.map((link, idx) => (
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

LotsIndex.layout = {
    breadcrumbs: [{ title: 'Lots & Tantièmes', href: '/lots' }],
};

<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/lots.php
 * TYPE           : Vue Métier / Registre du Parcellaire & Tantièmes
 * MODULE         : État Descriptif de Division & Clé de Répartition des Charges
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 36 : Détermination des parties privatives et communes
 *                  - Article 37 : Quote-part indivise proportionnelle (10 000 tantièmes)
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module gère l'état descriptif de division de la copropriété, consignant
 * l'ensemble des lots privatifs et leur quote-part de charges (tantièmes).
 *
 * Règles et contrôles :
 * 1. Clé de Répartition Obligatoire (10 000 tantièmes) :
 *    - Le cumul des tantièmes de tous les lots doit converger vers 10 000.
 *    - Un indicateur visuel signale le respect de l'équilibre légal (vert si 10 000,
 *      orange si le parc est en cours de modélisation).
 * 2. Caractéristiques Détaillées des Lots :
 *    - Numéro de lot, bâtiment/bloc, étage, destination (appartement, magasin,
 *      bureau, place de parking, cave), surface privative en mètres carrés.
 *    - Association avec le copropriétaire titulaire du droit de propriété.
 * 3. Barrière d'Écriture :
 *    - Contrôle du statut de licence pour bloquer l'ajout de lots en mode lecture seule.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// EXTRACTION DES LOTS ET DES COPROPRIÉTAIRES DU TENANT
// ----------------------------------------------------------------------------
// Chargement de l'ensemble des lots de l'immeuble avec les jointures propriétaires.
$lots = TenantDB::getLots();

// Chargement de la liste des copropriétaires pour le menu déroulant d'attribution.
$coproprietaires = TenantDB::getCoproprietaires();

// Calcul de la somme totale des quotes-parts de tantièmes attribuées.
$totalTantiemes = array_sum(array_column($lots, 'tantiemes'));

// Vérification du mode lecture seule lié à l'état de la licence.
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6">
    <!-- En-tête de section avec indicateur de conformité des tantièmes et bouton d'ajout -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Lots de Copropriété & Tantièmes</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Répartition légale des charges selon les quotes-parts de parties communes (Art. 36 et 37 Loi 18-00)
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Badge vérifiant l'atteinte des 10 000 tantièmes légaux -->
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold">
                Total : <span class="<?= $totalTantiemes === 10000 ? 'text-emerald-600' : 'text-amber-500' ?>"><?= $totalTantiemes ?></span> / 10 000
            </span>

            <!-- Contrôle de l'action d'ajout de lot -->
            <?php if (! $isReadOnly) { ?>
                <button
                    onclick="document.getElementById('modal-add-lot')?.classList.remove('hidden')"
                    class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
                >
                    <span>➕ Nouveau Lot</span>
                </button>
            <?php } else { ?>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                    <span>🔒</span>
                    <span>Ajout lot désactivé (Lecture seule)</span>
                </span>
            <?php } ?>
        </div>
    </div>

    <!-- Tableau de l'état descriptif de division -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Numéro Lot</th>
                        <th class="p-3.5">Immeuble & Étage</th>
                        <th class="p-3.5">Nature / Type</th>
                        <th class="p-3.5">Surface (m²)</th>
                        <th class="p-3.5">Tantièmes (/ 10 000)</th>
                        <th class="p-3.5">Copropriétaire Titulaire</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($lots)) { ?>
                        <!-- État affiché lorsqu'aucun lot n'a encore été créé -->
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400">Aucun lot créé dans cette copropriété.</td>
                        </tr>
                    <?php } else { ?>
                        <!-- Boucle d'affichage de chaque lot -->
                        <?php foreach ($lots as $l) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white">Lot <?= htmlspecialchars($l['numero']) ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($l['immeuble']) ?> &bull; Étage <?= $l['etage'] ?></td>
                                <td class="p-3.5 capitalize text-slate-600 dark:text-slate-400"><?= htmlspecialchars($l['type']) ?></td>
                                <td class="p-3.5 font-mono text-slate-700 dark:text-slate-300"><?= $l['surface'] ?> m²</td>
                                <td class="p-3.5 font-mono font-bold text-blue-600 dark:text-blue-400"><?= $l['tantiemes'] ?></td>
                                <td class="p-3.5 font-medium text-slate-800 dark:text-slate-200">
                                    <?= htmlspecialchars(($l['coproprietaire_prenom'] ?? '').' '.($l['coproprietaire_nom'] ?? 'Non affecté')) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE DE CRÉATION D'UN NOUVEAU LOT (ACCESSIBLE SI NON VERROUILLÉ) -->
<?php if (! $isReadOnly) { ?>
<div id="modal-add-lot" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
    <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <h3 class="font-bold text-sm">Ajouter un Lot de Copropriété</h3>
            <button onclick="document.getElementById('modal-add-lot').classList.add('hidden')" class="text-slate-400">&times;</button>
        </div>
        <form action="actions/add_lot.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Numéro du Lot *</label>
                    <input type="text" name="numero" required placeholder="Ex: 204" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Immeuble / Bloc</label>
                    <input type="text" name="immeuble" value="Principal" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Étage</label>
                    <input type="number" name="etage" value="1" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Type de Lot</label>
                    <select name="type" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                        <option value="appartement">Appartement</option>
                        <option value="magasin">Magasin / Commerce</option>
                        <option value="bureau">Bureau Professionnel</option>
                        <option value="parking">Place Parking</option>
                        <option value="box">Box / Cave</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Quote-part Tantièmes *</label>
                    <input type="number" name="tantiemes" required value="250" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Surface Habitable (m²)</label>
                    <input type="number" step="0.1" name="surface" placeholder="85.5" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>
            </div>
            <div>
                <label class="block font-semibold mb-1">Affecter au Copropriétaire</label>
                <select name="coproprietaire_id" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                    <option value="">-- Non affecté --</option>
                    <?php foreach ($coproprietaires as $c) { ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-add-lot').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-zinc-900 rounded-xl">Annuler</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-bold rounded-xl shadow">Enregistrer le Lot</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

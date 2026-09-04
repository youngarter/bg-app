<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/appels.php
 * TYPE           : Vue Métier / Échéancier des Appels de Fonds & Cotisations
 * MODULE         : Recouvrement, Provisions sur Charges & Clé de Répartition
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 18 : Exécution du budget prévisionnel voté en AG
 *                  - Article 25 : Exigibilité des créances et relances
 *                  - Article 36 & 37 : Répartition proportionnelle aux tantièmes
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module orchestre l'émission formelle des appels de fonds auprès des
 * copropriétaires, rendant les cotisations exigibles en droit marocain.
 *
 * Fonctionnalités majeures :
 * 1. Suggestions Intelligentes basées sur le Budget Voté en AG :
 *    - Récupère le budget annuel voté ($budgetVote) et sa périodicité ($frequenceVotee).
 *    - Calcule automatiquement le montant suggéré ($suggestedAmount) :
 *      * Trimestrielle : Budget / 4
 *      * Mensuelle     : Budget / 12
 *      * Semestrielle  : Budget / 2
 *      * Annuelle      : Totalité du budget
 * 2. Ventilation Algorithmique Temps Réel par Lot :
 *    - Formule légale Dahir n° 1-02-298 :
 *      QuotePartDue = MontantTotalAppele * (TantiemesLot / TotalTantiemes)
 *    - Un tableau dynamique JavaScript recalcule instantanément la cotisation de
 *      chaque appartement au centime près dès que le montant total est modifié.
 * 3. Consultation & Traçabilité :
 *    - Consultation de la ventilation détaillée de n'importe quel appel antérieur.
 *    - Barrière de modification si la copropriété est en lecture seule.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES DONNÉES DE L'EXERCICE ET DU BUDGET VOTÉ EN AG
// ----------------------------------------------------------------------------
// Exercice comptable sélectionné.
$ex = (int) ($selectedExercice ?? date('Y'));

// Liste de tous les appels de fonds émis pour cet exercice.
$appels = TenantDB::getAppels($ex);

// Récupération des résolutions de l'Assemblée Générale ayant voté le budget.
$votedAg = TenantDB::getVotedBudgetInfo($ex);

// Liste des lots privatifs pour application de la clé de tantièmes.
$lots = TenantDB::getLots();

// Somme des tantièmes de la copropriété (10 000 par défaut légal).
$totalTantiemes = array_sum(array_column($lots, 'tantiemes'));
if ($totalTantiemes <= 0) {
    $totalTantiemes = 10000;
}

// Vérification de la licence commerciale (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();

// Messages flash transmis en URL.
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

// ----------------------------------------------------------------------------
// CALCUL DU MONTANT SUGGÉRÉ SELON LA PÉRIODICITÉ VOTÉE EN AG
// ----------------------------------------------------------------------------
$budgetVote = (float) ($votedAg['budget_annuel_vote'] ?? 0);
$frequenceVotee = $votedAg['frequence_appels'] ?? 'trimestrielle';
$suggestedAmount = 0.0;

if ($budgetVote > 0) {
    switch ($frequenceVotee) {
        case 'mensuelle':
            // 12 appels de fonds par an
            $suggestedAmount = round($budgetVote / 12, 2);
            break;
        case 'semestrielle':
            // 2 appels de fonds par an
            $suggestedAmount = round($budgetVote / 2, 2);
            break;
        case 'annuelle':
            // Appel unique global
            $suggestedAmount = $budgetVote;
            break;
        case 'trimestrielle':
        default:
            // 4 appels trimestriels standard
            $suggestedAmount = round($budgetVote / 4, 2);
            break;
    }
}
?>

<div class="space-y-6">
    <!-- En-tête de section avec bouton d'émission d'appel -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Appels de Fonds & Cotisations</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Échéancier des provisions pour charges courantes et fonds de travaux (Art. 18, 25 & 36 Loi 18-00)</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Contrôle d'accès à l'émission d'appels -->
            <?php if (! $isReadOnly) { ?>
                <button
                    type="button"
                    onclick="openAppelModal()"
                    class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md"
                >
                    <span>➕ Émettre un Appel de Fonds</span>
                </button>
            <?php } else { ?>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                    <span>🔒 Émission désactivée (Lecture seule)</span>
                </span>
            <?php } ?>
        </div>
    </div>

    <!-- Alertes et Notifications d'exécution -->
    <?php if ($msg === 'appel_created') { ?>
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs flex items-center gap-2 shadow-sm">
            <span class="text-lg">✅</span>
            <div>
                <strong>Appel de fonds émis avec succès !</strong> Les quotes-parts ont été ventilées selon les tantièmes de chaque lot. Les créances correspondantes sont désormais exigibles.
            </div>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-red-800 dark:text-red-200 text-xs flex items-center gap-2 shadow-sm">
            <span class="text-lg">⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php } ?>

    <!-- Bannière récapitulative du Budget Voté en AG -->
    <div class="p-4 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-lg">
                🏛️
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900 dark:text-white">
                    Budget Prévisionnel Voté en Assemblée Générale (Exercice <?= $ex ?>)
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                    <?php if ($budgetVote > 0) { ?>
                        Budget annuel approuvé : <strong class="text-blue-600 dark:text-blue-400 font-mono"><?= formatMAD($budgetVote) ?></strong>
                        &bull; Périodicité votée : <strong class="capitalize"><?= htmlspecialchars($frequenceVotee) ?></strong>
                        <?php if (! empty($votedAg['rubriques_array'])) { ?>
                            &bull; <span class="text-slate-400"><?= count($votedAg['rubriques_array']) ?> rubriques estimatives configurées</span>
                        <?php } ?>
                    <?php } else { ?>
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">Aucun budget prévisionnel n'a encore été voté pour cet exercice.</span>
                        <span>Veuillez enregistrer le PV d'Assemblée Générale pour officialiser les cotisations.</span>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($budgetVote <= 0 && ! $isReadOnly) { ?>
                <a
                    href="index.php?tenant=<?= urlencode($guid) ?>&page=assemblees"
                    class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold hover:bg-amber-100 transition whitespace-nowrap"
                >
                    🗳️ Enregistrer l'AG & Budget &rarr;
                </a>
            <?php } ?>
        </div>
    </div>

    <!-- Tableau chronologique des Appels de Fonds émis -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Numéro Appel</th>
                        <th class="p-3.5">Type & Nature</th>
                        <th class="p-3.5">Exercice</th>
                        <th class="p-3.5">Période</th>
                        <th class="p-3.5">Date Exigibilité</th>
                        <th class="p-3.5">Montant Appelé</th>
                        <th class="p-3.5">Statut</th>
                        <th class="p-3.5 text-right">Ventilation Lots</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($appels)) { ?>
                        <!-- État vide lorsqu'aucun appel n'a encore été émis -->
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                <div class="text-xl mb-1">📢</div>
                                <div class="font-bold text-slate-600 dark:text-slate-300">Aucun appel de fonds émis pour l'exercice <?= $ex ?>.</div>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Conformément à la loi 18-00, les créances et impayés ne sont exigibles qu'après émission formelle d'un appel de fonds.
                                </p>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <!-- Boucle d'affichage de chaque appel émis -->
                        <?php foreach ($appels as $a) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5 font-bold font-mono text-blue-600 dark:text-blue-400"><?= htmlspecialchars($a['numero']) ?></td>
                                <td class="p-3.5 capitalize text-slate-800 dark:text-slate-200"><?= str_replace('_', ' ', $a['type']) ?></td>
                                <td class="p-3.5 font-mono text-slate-600 dark:text-slate-400"><?= $a['exercice'] ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($a['periode'] ?: 'Trimestre') ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= formatDateFR($a['date_exigibilite']) ?></td>
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white font-mono"><?= formatMAD((float) $a['montant_total']) ?></td>
                                <td class="p-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-600 border border-blue-500/20 uppercase">
                                        <?= strtoupper($a['statut']) ?>
                                    </span>
                                </td>
                                <td class="p-3.5 text-right">
                                    <!-- Bouton ouvrant la modale de ventilation détaillée par lot -->
                                    <button
                                        type="button"
                                        onclick="showBreakdown(<?= (float) $a['montant_total'] ?>, '<?= htmlspecialchars(addslashes($a['numero'].' - '.$a['periode'])) ?>')"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold transition"
                                    >
                                        👁️ Voir Répartition
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE D'ÉMISSION D'UN NOUVEL APPEL DE FONDS AVEC CALCUL DES QUOTES-PARTS EN TEMPS RÉEL -->
<?php if (! $isReadOnly) { ?>
<div id="modal-add-appel" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
    <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-5 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold">
                    📢
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Émettre un Appel de Fonds</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Calcul automatique des quotes-parts dues par appartement selon les tantièmes</p>
                </div>
            </div>
            <button onclick="document.getElementById('modal-add-appel').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl transition text-lg">
                &times;
            </button>
        </div>

        <form action="actions/add_appel.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Exercice Concerné *</label>
                    <input
                        type="number"
                        name="exercice"
                        id="appel-form-exercice"
                        required
                        value="<?= $ex ?>"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Nature de l'Appel *</label>
                    <select
                        name="type"
                        required
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold focus:border-blue-500 focus:outline-none"
                    >
                        <option value="charges_courantes" selected>Charges Courantes (Fonctionnement)</option>
                        <option value="fonds_travaux">Fonds de Travaux (Réserve légale Art. 18)</option>
                        <option value="travaux_exceptionnels">Travaux Exceptionnels (Votés en AGE)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Date d'Exigibilité *</label>
                    <input
                        type="date"
                        name="date_exigibilite"
                        required
                        value="<?= date('Y-m-d', strtotime('+15 days')) ?>"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Période / Libellé de l'Appel *</label>
                    <input
                        type="text"
                        name="periode"
                        required
                        placeholder="Ex: 1er Trimestre <?= $ex ?>, Janvier <?= $ex ?>..."
                        value="1er Trimestre <?= $ex ?>"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300">Montant Total à Appeler (DH TTC) *</label>
                        <!-- Raccourci appliquant le montant théorique calculé depuis le budget de l'AG -->
                        <?php if ($suggestedAmount > 0) { ?>
                            <button
                                type="button"
                                onclick="applySuggestedAmount(<?= $suggestedAmount ?>)"
                                class="text-[10px] text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                            >
                                Suggérer budget AG (<?= formatMAD($suggestedAmount) ?>)
                            </button>
                        <?php } ?>
                    </div>
                    <input
                        type="number"
                        step="0.01"
                        name="montant_total"
                        id="modal-montant-total"
                        required
                        placeholder="0.00"
                        value="<?= $suggestedAmount > 0 ? $suggestedAmount : '' ?>"
                        oninput="recalcBreakdownTable(this.value)"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold text-blue-600 focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Commentaire / Instructions de paiement (facultatif)</label>
                <input
                    type="text"
                    name="description"
                    placeholder="Ex: Paiement par virement bancaire sur le compte de la copropriété avant le 15 du mois."
                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                />
            </div>

            <!-- TABLEAU DE VENTILATION DYNAMIQUE DES COTISATIONS DUES PAR APPARTEMENT -->
            <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-zinc-800">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                        <span>🏢</span>
                        <span>Répartition des Cotisations Dues par Appartement (<?= count($lots) ?> lots &bull; <?= $totalTantiemes ?> tantièmes)</span>
                    </span>
                    <span class="text-[11px] text-slate-500">Formule : Montant &times; (Tantièmes / <?= $totalTantiemes ?>)</span>
                </div>

                <div class="border border-slate-200 dark:border-zinc-800 rounded-2xl overflow-hidden max-h-52 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800 text-[10px] font-bold text-slate-500 uppercase sticky top-0 z-10">
                            <tr>
                                <th class="p-2.5">Lot / Apt</th>
                                <th class="p-2.5">Copropriétaire Attribué</th>
                                <th class="p-2.5">Tantièmes</th>
                                <th class="p-2.5 text-right">Cotisation Dure (DH)</th>
                            </tr>
                        </thead>
                        <tbody id="breakdown-preview-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800/60 font-mono">
                            <?php if (empty($lots)) { ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-slate-400 font-sans">
                                        Aucun lot configuré dans cette résidence.
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($lots as $lot) { ?>
                                    <?php
                                        $copNom = trim(($lot['coproprietaire_prenom'] ?? '').' '.($lot['coproprietaire_nom'] ?? ''));
                                    $t = (int) ($lot['tantiemes'] ?? 0);
                                    $part = ($suggestedAmount > 0 && $totalTantiemes > 0) ? round($suggestedAmount * ($t / $totalTantiemes), 2) : 0.0;
                                    ?>
                                    <tr class="lot-row" data-tantiemes="<?= $t ?>">
                                        <td class="p-2 font-bold text-slate-900 dark:text-zinc-100">
                                            Lot <?= htmlspecialchars($lot['numero']) ?>
                                            <span class="text-[10px] text-slate-400 font-normal capitalize font-sans">(<?= htmlspecialchars($lot['type']) ?>)</span>
                                        </td>
                                        <td class="p-2 text-slate-600 dark:text-zinc-400 font-sans">
                                            <?= htmlspecialchars($copNom ?: 'Non attribué') ?>
                                        </td>
                                        <td class="p-2 text-slate-500">
                                            <?= $t ?> / <?= $totalTantiemes ?>
                                        </td>
                                        <td class="p-2 text-right font-bold text-blue-600 dark:text-blue-400 lot-part-cell">
                                            <?= number_format($part, 2, ',', ' ') ?> DH
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Boutons d'action de la modale -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-zinc-800">
                <button
                    type="button"
                    onclick="document.getElementById('modal-add-appel').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl border border-slate-200 dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-900 font-semibold transition text-xs"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition text-xs shadow-md"
                >
                    📢 Émettre et Notifier les Cotisations
                </button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<!-- MODALE D'AFFICHAGE DE LA RÉPARTITION D'UN APPEL EXISTANT -->
<div id="modal-view-breakdown" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
    <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white" id="view-breakdown-title">Répartition des Cotisations</h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400" id="view-breakdown-sub">Montant exigible par appartement selon tantièmes</p>
            </div>
            <button onclick="document.getElementById('modal-view-breakdown').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl transition text-lg">
                &times;
            </button>
        </div>

        <div class="border border-slate-200 dark:border-zinc-800 rounded-2xl overflow-hidden max-h-96 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800 text-[10px] font-bold text-slate-500 uppercase sticky top-0">
                    <tr>
                        <th class="p-2.5">Lot</th>
                        <th class="p-2.5">Copropriétaire</th>
                        <th class="p-2.5">Tantièmes</th>
                        <th class="p-2.5 text-right">Quote-Part Dûe</th>
                    </tr>
                </thead>
                <tbody id="view-breakdown-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800/60 font-mono">
                    <!-- Contenu injecté dynamiquement par JavaScript -->
                </tbody>
            </table>
        </div>

        <div class="flex justify-end pt-2">
            <button
                type="button"
                onclick="document.getElementById('modal-view-breakdown').classList.add('hidden')"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 text-slate-800 dark:text-slate-200 font-semibold transition text-xs"
            >
                Fermer
            </button>
        </div>
    </div>
</div>

<!-- SCRIPTS JAVASCRIPT : GESTION DES MODALES ET RECALCUL EN TEMPS RÉEL -->
<script>
// Transfert des données des lots depuis PHP vers un objet JavaScript pour manipulation côté client
const LOTS_DATA = <?= json_encode(array_map(function ($l) {
    return [
        'id' => $l['id'],
        'numero' => $l['numero'],
        'type' => $l['type'],
        'tantiemes' => (int) ($l['tantiemes'] ?? 0),
        'coproprietaire_nom' => trim(($l['coproprietaire_prenom'] ?? '').' '.($l['coproprietaire_nom'] ?? '')) ?: 'Non attribué',
    ];
}, $lots)) ?>;
const TOTAL_TANTIEMES = <?= $totalTantiemes ?>;

// Ouvre la modale d'ajout d'appel et initialise le tableau de répartition
function openAppelModal() {
    document.getElementById('modal-add-appel')?.classList.remove('hidden');
    const input = document.getElementById('modal-montant-total');
    if (input && input.value) {
        recalcBreakdownTable(input.value);
    }
}

// Applique le montant théorique suggéré et recalcule les cotisations
function applySuggestedAmount(amt) {
    const input = document.getElementById('modal-montant-total');
    if (input) {
        input.value = amt;
        recalcBreakdownTable(amt);
    }
}

// Recalcule en direct la quote-part de chaque lot au fur et à mesure de la saisie
function recalcBreakdownTable(totalVal) {
    const total = parseFloat(totalVal) || 0;
    const rows = document.querySelectorAll('#breakdown-preview-tbody .lot-row');
    rows.forEach(row => {
        const t = parseInt(row.getAttribute('data-tantiemes')) || 0;
        const part = TOTAL_TANTIEMES > 0 ? (total * (t / TOTAL_TANTIEMES)) : 0;
        const cell = row.querySelector('.lot-part-cell');
        if (cell) {
            cell.textContent = part.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH';
        }
    });
}

// Affiche la répartition exacte d'un appel déjà existant dans une modale dédiée
function showBreakdown(totalAmount, appelLabel) {
    const title = document.getElementById('view-breakdown-title');
    const sub = document.getElementById('view-breakdown-sub');
    const tbody = document.getElementById('view-breakdown-tbody');

    if (title) title.textContent = 'Répartition : ' + appelLabel;
    if (sub) sub.textContent = 'Total appelé : ' + totalAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' DH';

    if (tbody) {
        tbody.innerHTML = '';
        if (LOTS_DATA.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-slate-400 font-sans">Aucun lot configuré.</td></tr>';
        } else {
            LOTS_DATA.forEach(lot => {
                const part = TOTAL_TANTIEMES > 0 ? (totalAmount * (lot.tantiemes / TOTAL_TANTIEMES)) : 0;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2 font-bold text-slate-900 dark:text-zinc-100">Lot ${lot.numero} <span class="text-[10px] text-slate-400 font-normal font-sans">(${lot.type})</span></td>
                    <td class="p-2 text-slate-600 dark:text-zinc-400 font-sans">${lot.coproprietaire_nom}</td>
                    <td class="p-2 text-slate-500">${lot.tantiemes} / ${TOTAL_TANTIEMES}</td>
                    <td class="p-2 text-right font-bold text-blue-600 dark:text-blue-400">${part.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} DH</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    document.getElementById('modal-view-breakdown')?.classList.remove('hidden');
}
</script>

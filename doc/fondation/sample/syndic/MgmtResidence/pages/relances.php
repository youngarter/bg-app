<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/relances.php
 * TYPE           : Vue Métier / Contentieux & Recouvrement des Créances
 * MODULE         : Impayés, Calcul des Soldes Débiteurs & Mises en Demeure
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 25 : Procédure de mise en demeure et recouvrement forcé
 *                  - Injonction de payer et hypothèque légale de la copropriété
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module assure le suivi rigoureux et contradictoire des arriérés de charges
 * communes pesant sur chaque copropriétaire pour l'exercice en cours.
 *
 * Algorithme de calcul du solde débiteur :
 * 1. Tantièmes Détenus :
 *    - Somme des tantièmes de l'ensemble des lots rattachés au copropriétaire ($copTantiemes).
 * 2. Quote-part de Charges Appelées ($totalAppeleCop) :
 *    - Formule mathématique :
 *      totalAppeleCop = totalAppeleGlobal * (copTantiemes / totalTantiemes)
 * 3. Total des Règlements Encaissés ($totalPayeCop) :
 *    - Somme des quittances de paiements validées pour ce copropriétaire durant l'exercice.
 * 4. Solde Débiteur ($soldeImpaye) :
 *    - soldeImpaye = max(0, totalAppeleCop - totalPayeCop)
 *    - Si soldeImpaye > 0 : statut Débiteur avec montant en souffrance et bouton de relance.
 *    - Si soldeImpaye == 0 : statut À Jour certifié.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// EXTRACTION DES DONNÉES DU COCKPIT ET DES COMPTES COPROPRIÉTAIRES
// ----------------------------------------------------------------------------
// Exercice comptable sélectionné.
$ex = (int) ($selectedExercice ?? date('Y'));

// Synthèse financière globale (montant total des appels de charges émis, etc.).
$cockpit = TenantDB::getFinancialCockpit($ex);

// Liste de tous les copropriétaires de l'immeuble.
$coproprietaires = TenantDB::getCoproprietaires();

// Ensemble des paiements enregistrés pour l'exercice considéré.
$paiements = TenantDB::getPaiements($ex);

// Liste des lots pour calcul de la quote-part privative de tantièmes.
$lots = TenantDB::getLots();

// Récupération de la base totale des tantièmes (10 000 par défaut légal).
$totalTantiemes = TenantDB::getTotalTantiemes();
if ($totalTantiemes <= 0) {
    $totalTantiemes = 10000;
}
?>

<div class="space-y-6">
    <!-- En-tête de section avec badge du cumul des impayés de l'exercice -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Impayés & Contentieux</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Suivi des arriérés de charges et génération des lettres de relance / mises en demeure (Art. 25 Loi 18-00)</p>
        </div>
        <div class="px-3.5 py-1.5 rounded-xl bg-rose-50 dark:bg-red-950/40 border border-rose-200 dark:border-red-800 text-rose-700 dark:text-red-300 font-bold text-xs font-mono">
            Total Dû (Exercice <?= $ex ?>) : <?= formatMAD($cockpit['totalImpayes']) ?>
        </div>
    </div>

    <!-- Tableau de suivi individuel des créances et impayés -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Copropriétaire Débiteur</th>
                        <th class="p-3.5">Lots & Tantièmes</th>
                        <th class="p-3.5">Contact</th>
                        <th class="p-3.5">Appelé / Payé</th>
                        <th class="p-3.5">Situation Solde</th>
                        <th class="p-3.5 text-right">Actions Relance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($coproprietaires)) { ?>
                        <!-- Aucun copropriétaire dans la base -->
                        <tr><td colspan="6" class="p-6 text-center text-slate-400">Aucun copropriétaire enregistré.</td></tr>
                    <?php } else { ?>
                        <!-- Parcours et évaluation comptable de chaque copropriétaire -->
                        <?php foreach ($coproprietaires as $c) { ?>
                            <?php
                                // 1. Filtrage des lots rattachés au copropriétaire
                                $copLots = array_filter($lots, fn ($l) => ($l['coproprietaire_id'] ?? '') === $c['id']);
                            $copTantiemes = array_sum(array_column($copLots, 'tantiemes'));

                            // 2. Calcul au prorata légal des charges appelées
                            $totalAppeleCop = ($totalTantiemes > 0 && $cockpit['totalAppele'] > 0)
                                ? round($cockpit['totalAppele'] * ($copTantiemes / $totalTantiemes), 2)
                                : 0.0;

                            // 3. Cumul des règlements effectifs du copropriétaire
                            $totalPayeCop = 0.0;
                            foreach ($paiements as $p) {
                                if ($p['coproprietaire_id'] === $c['id']) {
                                    $totalPayeCop += (float) ($p['montant'] ?? 0);
                                }
                            }

                            // 4. Déduction du solde restant dû (créance)
                            $soldeImpaye = max(0.0, round($totalAppeleCop - $totalPayeCop, 2));
                            $isDebiteur = $soldeImpaye > 0;
                            ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($c['civilite'].' '.$c['prenom'].' '.$c['nom']) ?></div>
                                    <div class="text-[11px] font-mono text-slate-400">CIN: <?= htmlspecialchars($c['cin'] ?: 'N/A') ?></div>
                                </td>
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200"><?= count($copLots) ?> lot(s)</div>
                                    <div class="text-[11px] text-slate-500 font-mono"><?= $copTantiemes ?> / <?= $totalTantiemes ?> tant.</div>
                                </td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($c['telephone'] ?: $c['email']) ?></td>
                                <td class="p-3.5 font-mono text-[11px]">
                                    <div>Appelé : <?= formatMAD($totalAppeleCop) ?></div>
                                    <div class="text-emerald-600 dark:text-emerald-400 font-semibold">Payé : <?= formatMAD($totalPayeCop) ?></div>
                                </td>
                                <td class="p-3.5">
                                    <!-- Badge de solde : Débiteur (alerte rose) ou À Jour (vert) -->
                                    <?php if ($isDebiteur) { ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-600 border border-rose-500/20 whitespace-nowrap">
                                            Reste Dû : <?= formatMAD($soldeImpaye) ?>
                                        </span>
                                    <?php } else { ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 whitespace-nowrap">
                                            <?= $cockpit['totalAppele'] == 0 ? 'À Jour (0 appel)' : 'À Jour (Soldé)' ?>
                                        </span>
                                    <?php } ?>
                                </td>
                                <td class="p-3.5 text-right">
                                    <!-- Déclencheur de génération de courrier de mise en demeure -->
                                    <button
                                        onclick="alert('Génération de la lettre de relance pour <?= addslashes($c['nom']) ?>.');"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-[11px]"
                                    >
                                        ✉️ Lettre Relance
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

<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/carnet.php
 * TYPE           : Vue Métier / Registre Technique Obligatoire
 * MODULE         : Maintenance, Sécurité & Carnet d'Entretien de l'Immeuble
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Traçabilité des interventions sur parties communes
 *                  - Contrôles périodiques des équipements collectifs
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module gère l'historique technique légal des opérations de maintenance,
 * d'entretien et de révision des installations communes de l'immeuble.
 *
 * Éléments suivis :
 * 1. Équipements Clés :
 *    - Ascenseurs (contrôles semestriels/annuels de conformité).
 *    - Groupes hydrophores, surpresseurs et bâches d'eau potables.
 *    - Systèmes d'extinction d'incendie (RIA) et colonnes montantes.
 *    - Portails automatiques et interphonie collective.
 * 2. Transparence Budgétaire :
 *    - Consignation de la date exacte, du prestataire tiers certifié, de la
 *      description exhaustive des travaux et du coût financier TTC.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DE L'HISTORIQUE TECHNIQUE DU TENANT
// ----------------------------------------------------------------------------
// Extraction de la liste chronologique des interventions enregistrées.
$carnet = TenantDB::getCarnet();
?>

<div class="space-y-6">
    <!-- En-tête de section avec sous-titre légal -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Carnet d'Entretien de l'Immeuble</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Historique légal des interventions techniques, maintenance et contrôles de sécurité (Loi 18-00)</p>
        </div>
    </div>

    <!-- Tableau récapitulatif des interventions techniques -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Équipement / Partie Commune</th>
                        <th class="p-3.5">Date Intervention</th>
                        <th class="p-3.5">Descriptif des Travaux</th>
                        <th class="p-3.5">Prestataire / Société</th>
                        <th class="p-3.5 text-right">Coût TTC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($carnet)) { ?>
                        <!-- État vide si aucune intervention n'a encore été consignée -->
                        <tr><td colspan="5" class="p-6 text-center text-slate-400">Aucune intervention enregistrée dans le carnet d'entretien.</td></tr>
                    <?php } else { ?>
                        <!-- Boucle d'affichage de chaque intervention technique -->
                        <?php foreach ($carnet as $c) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($c['equipement']) ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= formatDateFR($c['date_intervention']) ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($c['description']) ?></td>
                                <td class="p-3.5 font-medium text-slate-800 dark:text-slate-200"><?= htmlspecialchars($c['prestataire']) ?></td>
                                <td class="p-3.5 text-right font-bold font-mono"><?= formatMAD($c['cout']) ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

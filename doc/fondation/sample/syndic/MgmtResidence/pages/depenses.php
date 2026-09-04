<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/depenses.php
 * TYPE           : Vue Métier / Grand Livre des Dépenses & Factures
 * MODULE         : Comptabilité, Charges Courantes & Nomenclature Budgétaire
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Justification et pièces comptables probantes
 *                  - Imputation des dépenses par rubriques budgétaires
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module répertorie et ventile l'ensemble des dépenses et factures engagées
 * pour le fonctionnement, l'entretien et l'administration de l'immeuble.
 *
 * Fonctionnalités et traitements :
 * 1. Filtrage par Exercice Comptable :
 *    - Les dépenses sont chargées selon l'exercice sélectionné ($selectedExercice).
 * 2. Consolidation Financière :
 *    - Calcul instantané du montant total TTC décaissé ou engagé via array_sum().
 * 3. Barrière de Modification (Mode Lecture Seule) :
 *    - Si la copropriété est verrouillée pour impayé de licence ou choix admin,
 *      le bouton de saisie est désactivé et remplacé par un badge informatif.
 * 4. Détail d'Imputation :
 *    - Distinction des montants Hors Taxes (HT) et Toutes Taxes Comprises (TTC).
 *    - Catégorisation comptable (Ascenseur, Eau/Élec, Gardiennage, Nettoyage, etc.).
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// EXTRACTION DES DONNÉES COMPTABLES DE L'EXERCICE
// ----------------------------------------------------------------------------
// Chargement des dépenses pour l'exercice comptable actif (par défaut 2025).
$depenses = TenantDB::getDepenses((int) ($selectedExercice ?? 2025));

// Calcul de la somme cumulée des montants TTC de toutes les factures chargées.
$totalTTC = array_sum(array_column($depenses, 'montant_ttc'));

// Vérification de l'état de la licence commerciale du tenant (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6">
    <!-- En-tête de section avec indicateur de total et bouton de saisie -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Registre des Dépenses & Factures</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Imputation des charges courantes et travaux selon la nomenclature comptable de la copropriété
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Badge récapitulatif du total engagé -->
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold text-rose-600 dark:text-rose-400">
                Total TTC : <?= formatMAD($totalTTC) ?>
            </span>

            <!-- Contrôle d'accès en écriture selon statut de licence -->
            <?php if (! $isReadOnly) { ?>
                <button
                    onclick="document.getElementById('modal-depense')?.classList.remove('hidden')"
                    class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
                >
                    <span>➕ Saisir une Dépense</span>
                </button>
            <?php } else { ?>
                <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                    <span>🔒</span>
                    <span>Saisie désactivée (Lecture seule)</span>
                </span>
            <?php } ?>
        </div>
    </div>

    <!-- Tableau détaillé du journal des dépenses -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Date Facture</th>
                        <th class="p-3.5">Fournisseur / Prestataire</th>
                        <th class="p-3.5">Libellé & Description</th>
                        <th class="p-3.5">Catégorie Imputation</th>
                        <th class="p-3.5">Montant HT</th>
                        <th class="p-3.5">Montant TTC</th>
                        <th class="p-3.5 text-right">Statut Paiement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($depenses)) { ?>
                        <!-- État vide -->
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">Aucune facture enregistrée pour l'exercice <?= htmlspecialchars((string) ($selectedExercice ?? 2025)) ?>.</td>
                        </tr>
                    <?php } else { ?>
                        <!-- Lignes des dépenses -->
                        <?php foreach ($depenses as $d) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= formatDateFR($d['date']) ?></td>
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($d['fournisseur_nom']) ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($d['description']) ?></td>
                                <td class="p-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($d['categorie']) ?>
                                    </span>
                                </td>
                                <td class="p-3.5 font-mono text-slate-500"><?= formatMAD($d['montant_ht']) ?></td>
                                <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white"><?= formatMAD($d['montant_ttc']) ?></td>
                                <td class="p-3.5 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                                        <?= strtoupper($d['statut_paiement']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

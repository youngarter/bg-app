<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/paiements.php
 * TYPE           : Vue Métier / Journal des Règlements & Quittances
 * MODULE         : Trésorerie, Encaissements & Délivrance des Quittances
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Obligation de délivrance de reçu libératoire
 *                  - Traçabilité des modes d'encaissement et des références
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module centralise le journal chronologique des règlements de charges
 * effectués par les copropriétaires au titre de l'exercice budgétaire sélectionné.
 *
 * Fonctionnalités majeures :
 * 1. Suivi des Encaissements :
 *    - Numéro séquentiel officiel de la quittance (QUIT-YYYY-XXXX).
 *    - Identification du copropriétaire et du lot concerné.
 *    - Mode de règlement (Virement bancaire, Chèque avec n°, Versement espèces).
 * 2. Édition & Impression des Quittances :
 *    - Bouton d'accès direct générant la quittance officielle imprimable (quittance.php).
 * 3. Barrière d'Écriture :
 *    - Contrôle du statut de licence pour empêcher la saisie de nouveaux encaissements
 *      lorsque le mode lecture seule est activé.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES PAIEMENTS DE L'EXERCICE COMPTABLE ACTIF
// ----------------------------------------------------------------------------
// Chargement de l'ensemble des encaissements de l'exercice (par défaut 2025).
$paiements = TenantDB::getPaiements((int) ($selectedExercice ?? 2025));

// Vérification de la licence commerciale du tenant (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6">
    <!-- En-tête de section avec bouton d'enregistrement d'encaissement -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Journal des Encaissements & Quittances</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Historique des cotisations réglées avec délivrance obligatoire de quittance libératoire (Loi 18-00)
            </p>
        </div>

        <!-- Contrôle d'accès à la saisie de paiement -->
        <?php if (! $isReadOnly) { ?>
            <button
                onclick="document.getElementById('modal-paiement')?.classList.remove('hidden')"
                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
            >
                <span>➕ Nouvel Encaissement</span>
            </button>
        <?php } else { ?>
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                <span>🔒</span>
                <span>Encaissement désactivé (Lecture seule)</span>
            </span>
        <?php } ?>
    </div>

    <!-- Tableau du journal des encaissements -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">N° Quittance</th>
                        <th class="p-3.5">Copropriétaire</th>
                        <th class="p-3.5">Lot Concerne</th>
                        <th class="p-3.5">Date Règlement</th>
                        <th class="p-3.5">Mode & Référence</th>
                        <th class="p-3.5">Montant Encaissé</th>
                        <th class="p-3.5 text-right">Action Quittance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($paiements)) { ?>
                        <!-- État vide si aucun paiement n'est consigné pour cet exercice -->
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">Aucun paiement enregistré pour l'exercice <?= htmlspecialchars((string) ($selectedExercice ?? 2025)) ?>.</td>
                        </tr>
                    <?php } else { ?>
                        <!-- Lignes des quittances -->
                        <?php foreach ($paiements as $p) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5 font-mono font-bold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($p['numero_quittance']) ?></td>
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars(($p['coproprietaire_prenom'] ?? '').' '.($p['coproprietaire_nom'] ?? 'Copropriétaire')) ?>
                                </td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">Lot <?= htmlspecialchars($p['lot_numero'] ?? 'N/A') ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400"><?= formatDateFR($p['date_paiement']) ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">
                                    <div><?= htmlspecialchars(MODE_PAIEMENT_LABELS[$p['mode_paiement']] ?? $p['mode_paiement']) ?></div>
                                    <?php if ($p['reference']) { ?>
                                        <div class="text-[10px] font-mono text-slate-400"><?= htmlspecialchars($p['reference']) ?></div>
                                    <?php } ?>
                                </td>
                                <td class="p-3.5 font-bold text-emerald-600 dark:text-emerald-400"><?= formatMAD($p['montant']) ?></td>
                                <td class="p-3.5 text-right">
                                    <!-- Lien ouvrant la quittance imprimable dans un nouvel onglet -->
                                    <a
                                        href="quittance.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode($p['id']) ?>"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition text-[11px] font-semibold"
                                    >
                                        <span>📥 Imprimer Quittance</span>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

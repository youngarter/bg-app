<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : QUITTANCES LIBÉRATOIRES & PAIEMENTS
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * FONDEMENT LÉGAL ET VALEUR PROBANTE DE LA QUITTANCE :
 * ----------------------------------------------------------------------------
 * Ce module met à disposition du copropriétaire l'ensemble de ses quittances
 * de paiement générées de manière infalsifiable par le syndic.
 *
 * Principes juridiques régis par la Loi n° 18-00 (Dahir n° 1-02-298) :
 * - Article 25 : Le paiement des charges communes (provisions sur budget prévisionnel
 *   ou charges pour travaux) est libératoire dès délivrance de la quittance
 *   portant numéro de série unique, montant réglé et visa du syndic.
 * - Certificat d'acquit des charges (Mutation / Vente) :
 *   En cas d'aliénation d'un lot, le notaire instrumentaire exige obligatoirement
 *   la justification de l'apurement des charges via les quittances délivrées.
 * - Impression / Export conforme :
 *   Chaque quittance peut être visualisée et imprimée sous format officiel A4
 *   conforme aux exigences de la comptabilité de copropriété marocaine.
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DE L'HISTORIQUE DES PAIEMENTS DU COPROPRIÉTAIRE CONNECTÉ
// ============================================================================

/**
 * Exercice comptable actif ou sélectionné par le filtre (par défaut 2025).
 *
 * @var int $ex
 */
$ex = (int) ($selectedExercice ?? 2025);

/**
 * Récupération exhaustive de tous les versements du copropriétaire (toutes années
 * confondues pour garantir la traçabilité complète de l'apurement de compte).
 *
 * @var array<int, array<string, mixed>> $paiements Liste des quittances
 */
$paiements = ResidentDB::getResidentPaiements($copId, null);

/**
 * Cumul historique de l'ensemble des versements régularisés par le copropriétaire.
 *
 * @var float $totalHistorique
 */
$totalHistorique = 0.0;
foreach ($paiements as $p) {
    $totalHistorique += (float) ($p['montant'] ?? 0);
}
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DE LA VUE RÉSIDENT : QUITTANCES LIBÉRATOIRES          -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- En-tête de page avec titre et rappel du total versé historique -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Mes Quittances Libératoires
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Historique officiel de vos règlements de charges avec décharge légale (Art. 25 de la Loi 18-00)
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Badge du total cumulé versé au syndicat des copropriétaires -->
            <div class="px-4 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 font-bold text-xs">
                Total Versé Historique : <?= formatMAD($totalHistorique) ?>
            </div>
        </div>
    </div>

    <!-- Note législative officielle relative à la force libératoire de la quittance -->
    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-3">
        <span class="text-xl" aria-hidden="true">📜</span>
        <div class="space-y-1">
            <div class="font-bold">Valeur Légale de la Quittance (Dahir n° 1-02-298)</div>
            <p class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-300/90">
                Conformément aux dispositions de la Loi n° 18-00, chaque versement régularisé donne lieu à une quittance numérotée portant décharge libératoire du syndic. Ce document officiel est indispensable pour justifier de votre régularité lors des assemblées générales ou en cas de mutation/cession de votre lot chez le notaire (certificat d'acquit des charges).
            </p>
        </div>
    </div>

    <!-- Table des quittances délivrées avec bouton d'impression individuelle -->
    <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] overflow-hidden shadow-sm">
        <div class="p-4 bg-[#FDF8F5] dark:bg-[#15021E] border-b border-[#F0E4DC] dark:border-[#3D154F] flex items-center justify-between">
            <div class="text-xs font-bold text-slate-900 dark:text-white">
                Reçus & Quittances Délivrés (<?= count($paiements) ?>)
            </div>
            <div class="text-[11px] text-slate-500">
                Format d'impression officiel A4 conforme
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#FDF8F5]/80 dark:bg-[#14021C]/80 border-b border-[#F0E4DC] dark:border-[#3D154F] text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5">N° de Quittance</th>
                        <th class="p-3.5">Date de Règlement</th>
                        <th class="p-3.5">Lot Associé</th>
                        <th class="p-3.5">Mode de Versement</th>
                        <th class="p-3.5">Référence / Chèque / Virement</th>
                        <th class="p-3.5 text-right">Montant Réglé</th>
                        <th class="p-3.5 text-center">Document</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F0E4DC]/60 dark:divide-[#3D154F]/60">
                    <?php if (empty($paiements)) { ?>
                        <!-- Cas où aucun versement n'est encore enregistré au crédit du copropriétaire -->
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="text-2xl mb-1" aria-hidden="true">🧾</div>
                                Aucune quittance de paiement enregistrée à votre nom pour le moment.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <!-- Boucle d'affichage des quittances libératoires -->
                        <?php foreach ($paiements as $p) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-[#250832]/50 transition">
                                <!-- Numéro officiel et unique de la quittance comptable -->
                                <td class="p-3.5 font-mono font-bold text-[#D91C6E] dark:text-[#F26968]">
                                    <?= htmlspecialchars($p['numero_quittance']) ?>
                                </td>

                                <!-- Date de réalisation effective du versement -->
                                <td class="p-3.5 text-slate-700 dark:text-slate-300 font-medium">
                                    <?= formatDateFR($p['date_paiement']) ?>
                                </td>

                                <!-- Numéro et désignation du lot concerné par l'appel de fonds -->
                                <td class="p-3.5">
                                    <span class="font-bold text-slate-900 dark:text-white">Lot #<?= htmlspecialchars((string) ($p['lot_numero'] ?: '101')) ?></span>
                                    <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($p['lot_type'] ?? 'Appartement') ?></span>
                                </td>

                                <!-- Mode de règlement bancaire ou numéraire -->
                                <td class="p-3.5 text-slate-600 dark:text-slate-400 capitalize">
                                    <?= htmlspecialchars(MODE_PAIEMENT_LABELS[$p['mode_paiement']] ?? $p['mode_paiement']) ?>
                                </td>

                                <!-- Référence du bordereau bancaire, chèque ou transaction -->
                                <td class="p-3.5 font-mono text-slate-500 dark:text-slate-400">
                                    <?= htmlspecialchars($p['reference'] ?: 'SANS RÉFÉRENCE') ?>
                                </td>

                                <!-- Montant en Dirhams Marocains libéré par la quittance -->
                                <td class="p-3.5 text-right font-black text-slate-900 dark:text-white text-sm">
                                    <?= formatMAD((float) $p['montant']) ?>
                                </td>

                                <!-- Lien d'accès au document imprimable A4 officiel -->
                                <td class="p-3.5 text-center">
                                    <a
                                        href="quittance.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode((string) $p['id']) ?>"
                                        target="_blank"
                                        class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs transition inline-flex items-center gap-1.5 shadow-sm"
                                        title="Ouvrir le reçu officiel imprimable"
                                    >
                                        <span aria-hidden="true">🖨️</span>
                                        <span>Télécharger / Imprimer</span>
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

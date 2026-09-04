<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : ASSEMBLÉES GÉNÉRALES & PROCÈS-VERBAUX
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * CADRE JURIDIQUE ET DROIT D'INFORMATION DÉMOCRATIQUE :
 * ----------------------------------------------------------------------------
 * Ce module garantit le plein exercice des prérogatives légales des copropriétaires
 * en matière de démocratie et de gouvernance d'immeuble.
 *
 * Dispositions fondamentales de la Loi n° 18-00 (Dahir n° 1-02-298) :
 * - Article 16, 17 & 18 : L'Assemblée Générale est l'organe souverain du syndicat.
 *   Elle approuve les comptes, vote le budget annuel, nomme ou révoque le syndic
 *   et autorise les travaux d'amélioration.
 * - Article 21 : Droit inaliénable de consultation des procès-verbaux de séance.
 *   Le PV certifié, constatant les résultats de vote et les tantièmes représentés,
 *   s'impose à tous les copropriétaires même absents ou opposants.
 * - Quorum et Validité : Affichage transparent des tantièmes présents / représentés
 *   (base légale sur 10 000 tantièmes) et mention de la majorité qualifiée atteinte.
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DES DONNÉES D'ASSEMBLÉES GÉNÉRALES DU TENANT
// ============================================================================

/**
 * GUID unique du tenant actif résolu depuis la requête ou le cookie de session.
 *
 * @var string $guid
 */
$guid = $guid ?? TenantDB::resolveGuid();

/**
 * Historique des assemblées générales (ordinaires et extraordinaires) archivées.
 *
 * @var array<int, array<string, mixed>> $assemblees Liste des AG avec résolutions
 */
$assemblees = ResidentDB::getAssemblees();
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DE LA VUE RÉSIDENT : ASSEMBLÉES GÉNÉRALES & PV        -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- En-tête de page avec titre et compteur d'assemblées consignées -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Assemblées Générales & Procès-Verbaux
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Consultez les délibérations, votes de budgets et procès-verbaux officiels de votre copropriété
            </p>
        </div>

        <!-- Badge du nombre de séances archivées -->
        <div class="px-3.5 py-1.5 rounded-2xl bg-[#D91C6E]/10 border border-[#D91C6E]/20 text-[#D91C6E] dark:text-[#F26968] font-bold text-xs">
            <?= count($assemblees) ?> Assemblée(s) Archivée(s)
        </div>
    </div>

    <!-- Encart de rappel légal sur le droit d'information et la force exécutoire des votes -->
    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] text-xs text-slate-600 dark:text-slate-300 flex items-start gap-3">
        <span class="text-xl" aria-hidden="true">🗳️</span>
        <div class="space-y-1">
            <div class="font-bold text-slate-900 dark:text-white">
                Droit d'Information des Copropriétaires (Loi 18-00, Art. 18 & 21)
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                Tout copropriétaire dispose du droit de consulter les procès-verbaux de l'Assemblée Générale. Les résolutions régulièrement adoptées par l'Assemblée s'imposent à l'ensemble des copropriétaires et occupants de l'immeuble.
            </p>
        </div>
    </div>

    <!-- Liste chronologique des Assemblées Générales -->
    <div class="space-y-4">
        <?php if (empty($assemblees)) { ?>
            <!-- État vide si aucune assemblée n'a encore été enregistrée -->
            <div class="p-12 text-center text-slate-400 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm">
                Aucune assemblée générale enregistrée dans l'historique de cette copropriété.
            </div>
        <?php } else { ?>
            <!-- Itération sur chaque séance d'Assemblée Générale -->
            <?php foreach ($assemblees as $ag) { ?>
                <?php
                    // Qualification du caractère ordinaire (AGO) ou extraordinaire (AGE)
                    $isAGE = ($ag['type'] === 'extraordinaire');
                $changement = ! empty($ag['changement_syndic']);
                ?>
                <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
                    <!-- Bandeau supérieur : Date, nature de l'AG et bouton d'impression PV -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#F0E4DC]/60 dark:border-[#3D154F]/60">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Badge de la nature de la séance -->
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $isAGE ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30' : 'bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/30' ?>">
                                    <?= $isAGE ? '🚨 Assemblée Générale Extraordinaire (AGE)' : '📅 Assemblée Générale Ordinaire (AGO)' ?>
                                </span>
                                <!-- Mention spéciale en cas de passation ou élection de syndic -->
                                <?php if ($changement) { ?>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                                        🔄 Élection & Passation de Syndic
                                    </span>
                                <?php } ?>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                Séance du <?= formatDateFR($ag['date']) ?> &bull; Exercice <?= htmlspecialchars((string) ($ag['exercice'] ?? date('Y', strtotime($ag['date'] ?? 'now')))) ?>
                            </h3>
                        </div>

                        <!-- Bouton d'accès au Procès-Verbal officiel téléchargeable / imprimable A4 -->
                        <a
                            href="pv_assemblee.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode((string) $ag['id']) ?>"
                            target="_blank"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs shadow-md inline-flex items-center gap-2 transition self-start sm:self-center"
                        >
                            <span aria-hidden="true">📄</span>
                            <span>Consulter le PV d'AG</span>
                        </a>
                    </div>

                    <!-- Métriques légales : Quorum constaté, lieu de réunion, présidence de séance -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Quorum / Présence</div>
                            <div class="font-bold text-slate-900 dark:text-white mt-0.5">
                                <?= $ag['tantiemes_presents'] ?? 8500 ?> / 10 000 tantièmes
                            </div>
                        </div>

                        <div class="p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Lieu de Séance</div>
                            <div class="font-bold text-slate-900 dark:text-white mt-0.5">
                                <?= htmlspecialchars($ag['lieu'] ?: 'Hall de l\'immeuble') ?>
                            </div>
                        </div>

                        <div class="p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Président de Séance</div>
                            <div class="font-bold text-slate-900 dark:text-white mt-0.5">
                                <?= htmlspecialchars($ag['president_seance'] ?: 'Président élu') ?>
                            </div>
                        </div>

                        <div class="p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Statut Quorum</div>
                            <div class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                <?= htmlspecialchars(! empty($ag['statut_quorum']) ? $ag['statut_quorum'] : 'Quorum Atteint') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Ordre du jour officiel et synthèse des résolutions délibérées -->
                    <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2 text-xs">
                        <div class="font-bold text-slate-800 dark:text-slate-200">Ordre du Jour Débattu :</div>
                        <div class="text-slate-600 dark:text-slate-400 italic leading-relaxed">
                            <?= nl2br(htmlspecialchars($ag['ordre_du_jour'] ?: "1. Approbation des comptes annuels.\n2. Vote du budget prévisionnel.\n3. Travaux et entretien de l'immeuble.")) ?>
                        </div>
                        <?php if (! empty($ag['description'])) { ?>
                            <div class="pt-2 border-t border-[#F0E4DC]/60 dark:border-[#3D154F]/60 text-slate-700 dark:text-slate-300">
                                <strong>Synthèse des Résolutions :</strong> <?= htmlspecialchars($ag['description']) ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : GRANDS TRAVAUX & CHANTIERS DE L'IMMEUBLE
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * CADRE LÉGISLATIF ET GOUVERNANCE FINANCIÈRE :
 * ----------------------------------------------------------------------------
 * Ce module offre une visibilité totale aux résidents et copropriétaires sur les
 * chantiers d'envergure, rénovations et travaux exceptionnels votés en AG.
 *
 * Principes issus de la Loi n° 18-00 relative à la copropriété :
 * - Article 21 & 24 : Les travaux d'amélioration, réfection de toiture,
 *   ravalement de façade ou mise en conformité des installations nécessitent une
 *   décision expresse de l'Assemblée Générale statuant selon la majorité requise.
 * - Fonds de travaux (Réserve spéciale) : Les sommes appelées pour les grands travaux
 *   sont cantonnées et affectées exclusivement au projet voté.
 * - Devoir de reddition et transparence : Les copropriétaires peuvent suivre
 *   en temps réel le taux d'avancement physique (en pourcentage), le budget voté
 *   et le calendrier prévisionnel des interventions.
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DES CHANTIERS DEPUIS LA COUCHE D'ACCÈS RÉSIDENT
// ============================================================================

/**
 * Liste des grands travaux et projets de rénovation du tenant courant.
 *
 * @var array<int, array<string, mixed>> $projets Projets ordonnés
 */
$projets = ResidentDB::getProjets();
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DE LA VUE RÉSIDENT : GRANDS TRAVAUX ET CHANTIERS       -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- En-tête de section avec titre et compteur de chantiers en cours -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Grands Travaux & Chantiers Votés
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Suivi transparent des chantiers votés en Assemblée Générale et financés par le fonds de travaux
            </p>
        </div>

        <!-- Badge indiquant le nombre de projets recensés -->
        <div class="px-3.5 py-1.5 rounded-2xl bg-[#D91C6E]/10 border border-[#D91C6E]/20 text-[#D91C6E] dark:text-[#F26968] font-bold text-xs">
            <?= count($projets) ?> Projet(s) en Cours
        </div>
    </div>

    <!-- Encart pédagogique sur le fonctionnement des réserves et fonds de travaux -->
    <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] text-xs text-slate-600 dark:text-slate-300 flex items-start gap-3">
        <span class="text-xl" aria-hidden="true">🏗️</span>
        <div class="space-y-1">
            <div class="font-bold text-slate-900 dark:text-white">
                Fonds de Réserve et Décisions d'Assemblée Générale
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                Les travaux touchant au gros œuvre, à la sécurité ou à l'amélioration de l'immeuble sont soumis au vote préalable des copropriétaires. Les fonds appelés spécifiquement pour ces chantiers sont consignés sur un compte séparé jusqu'à réception définitive des travaux.
            </p>
        </div>
    </div>

    <!-- Liste verticale des chantiers et cartes de progression dynamique -->
    <div class="space-y-4">
        <?php if (empty($projets)) { ?>
            <!-- État vide si aucun grand chantier n'est actuellement initié -->
            <div class="p-12 text-center text-slate-400 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm">
                Aucun grand chantier planifié actuellement.
            </div>
        <?php } else { ?>
            <!-- Itération sur chaque projet de copropriété -->
            <?php foreach ($projets as $p) { ?>
                <?php
                    // Calcul normalisé du pourcentage de progression
                    $avancement = (int) ($p['avancement'] ?? 0);
                if ($p['statut'] === 'termine') {
                    $avancement = 100;
                } elseif ($avancement <= 0 && $p['statut'] === 'en_cours') {
                    $avancement = 45; // Valeur par défaut indicative si en cours
                }
                ?>
                <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
                    <!-- Bandeau supérieur du projet : Nom, statut et budget voté -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#F0E4DC]/60 dark:border-[#3D154F]/60">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($p['nom'] ?? $p['titre'] ?? 'Chantier') ?>
                                </h3>
                                <!-- Badge du statut opérationnel (terminé / en cours) -->
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $p['statut'] === 'termine' ? 'bg-emerald-500/15 text-emerald-600 border border-emerald-500/30' : 'bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/30' ?>">
                                    <?= htmlspecialchars($p['statut']) ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Démarrage prévu / effectif : <?= formatDateFR($p['date_debut'] ?? date('Y-m-d')) ?>
                            </p>
                        </div>

                        <!-- Budget voté en Assemblée Générale -->
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 uppercase font-bold">
                                Budget Voté en AG
                            </div>
                            <div class="text-lg font-black text-[#D91C6E] dark:text-[#F26968]">
                                <?= formatMAD((float) ($p['budget'] ?? $p['budget_vote'] ?? 0)) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Barre de progression visuelle du chantier -->
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                            <span>Avancement Global du Projet</span>
                            <span><?= $avancement ?>%</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 dark:bg-[#14021C] rounded-full overflow-hidden border border-[#F0E4DC] dark:border-[#3D154F]">
                            <div
                                class="h-full bg-gradient-to-r from-[#D91C6E] to-[#F27835] rounded-full transition-all duration-500"
                                style="width: <?= $avancement ?>%;"
                            ></div>
                        </div>
                    </div>

                    <!-- Description détaillée du cahier des charges des travaux -->
                    <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        <?= nl2br(htmlspecialchars($p['description'] ?? 'Travaux de rénovation et de valorisation du patrimoine immobilier.')) ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : CARNET D'ENTRETIEN TECHNIQUE DE L'IMMEUBLE
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * CADRE JURIDIQUE ET OBJECTIFS LÉGAUX :
 * ----------------------------------------------------------------------------
 * Ce module offre aux copropriétaires et résidents une consultation transparente
 * et auditée du carnet d'entretien technique de la copropriété.
 *
 * Fondements selon la Loi n° 18-00 régissant la copropriété des immeubles bâtis :
 * - Article 18 & 26 : Obligation pour le syndic de veiller à la conservation,
 *   la garde et l'entretien des parties communes et équipements collectifs
 *   (ascenseurs, suppresseurs d'eau, colonnes montantes, sécurité incendie).
 * - Droit de contrôle des copropriétaires : Tout copropriétaire a le droit légal
 *   d'être informé de la nature des interventions exécutées dans l'immeuble,
 *   des prestataires mandatés et des coûts imputés sur les fonds de copropriété.
 *
 * DONNÉES PRÉSENTÉES :
 * - Historique chronologique complet des visites techniques, révisions périodiques,
 *   curages des colonnes et maintenances préventives/curatives.
 * - Identification du prestataire titulaire de l'agrément ou du contrat d'entretien.
 * - Coût TTC imputé à la charge de la communauté des copropriétaires.
 */

declare(strict_types=1);

// ============================================================================
// 1. CHARGEMENT DES DONNÉES DU CARNET D'ENTRETIEN VIA L'API DE LECTURE RÉSIDENT
// ============================================================================

/**
 * Récupération du journal des interventions techniques enregistrées sur la base
 * de données SQLite du tenant courant.
 *
 * @var array<int, array<string, mixed>> $carnet Liste ordonnée antéchronologique
 */
$carnet = ResidentDB::getCarnet();
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DE LA VUE RÉSIDENT : CARNET D'ENTRETIEN               -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- En-tête de page avec titre explicatif et compteur d'interventions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Carnet d'Entretien de l'Immeuble
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Registre légal des opérations de maintenance, contrôles périodiques et sécurité des équipements communs
            </p>
        </div>

        <!-- Badge récapitulatif du nombre d'interventions tracées -->
        <div class="px-3.5 py-1.5 rounded-2xl bg-[#D91C6E]/10 border border-[#D91C6E]/20 text-[#D91C6E] dark:text-[#F26968] font-bold text-xs">
            <?= count($carnet) ?> Intervention(s) Enregistrée(s)
        </div>
    </div>

    <!-- Bannière informative sur la sécurité technique et la conformité légale -->
    <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] text-xs text-slate-600 dark:text-slate-300 flex items-start gap-3">
        <span class="text-xl" aria-hidden="true">🛠️</span>
        <div class="space-y-1">
            <div class="font-bold text-slate-900 dark:text-white">
                Transparence Technique & Sécurité des Résidents
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                Le carnet d'entretien consigne l'historique complet des visites techniques, révisions d'ascenseurs, curages des canalisations, analyses des eaux et maintenances des extincteurs. Ce registre garantit la longévité de votre patrimoine immobilier et la conformité aux normes de sécurité en vigueur au Maroc.
            </p>
        </div>
    </div>

    <!-- Grille tabulaire : Registre officiel des interventions techniques -->
    <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <!-- Entêtes de colonnes conformes aux standards de gestion technique -->
                <thead class="bg-[#FDF8F5] dark:bg-[#14021C] border-b border-[#F0E4DC] dark:border-[#3D154F] text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5">Date Intervention</th>
                        <th class="p-3.5">Équipement / Poste</th>
                        <th class="p-3.5">Prestataire Agréé</th>
                        <th class="p-3.5">Nature de l'Opération</th>
                        <th class="p-3.5 text-right">Montant Pris en Charge</th>
                    </tr>
                </thead>

                <!-- Corps de table : Liste itérative des interventions ou état vide -->
                <tbody class="divide-y divide-[#F0E4DC]/60 dark:divide-[#3D154F]/60">
                    <?php if (empty($carnet)) { ?>
                        <!-- Cas où aucune intervention n'est encore inscrite au registre -->
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">
                                Aucun historique d'intervention consigné pour le moment.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <!-- Parcours chronologique des entrées de maintenance technique -->
                        <?php foreach ($carnet as $c) { ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-[#250832]/50 transition">
                                <!-- Date officielle de l'intervention (formatée en français JJ/MM/AAAA) -->
                                <td class="p-3.5 text-slate-700 dark:text-slate-300 font-medium">
                                    <?= formatDateFR($c['date_intervention']) ?>
                                </td>

                                <!-- Équipement technique ou poste commun concerné (ex: Ascenseur, Groupe électrogène) -->
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($c['equipement'] ?? 'Parties Communes') ?>
                                </td>

                                <!-- Prestataire technique ou société sous contrat d'entretien -->
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">
                                    <?= htmlspecialchars($c['prestataire'] ?? 'Société Spécialisée') ?>
                                </td>

                                <!-- Description succincte de l'acte de maintenance (révision, remplacement, curage) -->
                                <td class="p-3.5 text-slate-600 dark:text-slate-300">
                                    <?= htmlspecialchars($c['description'] ?? 'Contrôle technique préventif') ?>
                                </td>

                                <!-- Coût de l'opération pris en charge sur le budget de la copropriété -->
                                <td class="p-3.5 text-right font-black text-slate-900 dark:text-white">
                                    <?= formatMAD((float) ($c['cout'] ?? $c['montant'] ?? 0)) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

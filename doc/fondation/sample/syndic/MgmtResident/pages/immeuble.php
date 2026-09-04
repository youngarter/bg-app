<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : FICHE D'IDENTITÉ DE L'IMMEUBLE & SYNDIC
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * FONDEMENT JURIDIQUE ET RENSEIGNEMENTS IMMOBILIERS OFFICIELS :
 * ----------------------------------------------------------------------------
 * Ce module présente la fiche d'identité officielle de la copropriété ainsi que
 * les coordonnées institutionnelles du bureau du syndic mandataire.
 *
 * Éléments requis par la Loi n° 18-00 (Dahir n° 1-02-298) :
 * - Titre Foncier Mère : Référence foncière cadastrale de l'assise foncière
 *   délivrée par l'Agence Nationale de la Conservation Foncière (ANCFCC).
 * - Répartition des tantièmes : Totalité de la propriété indivise répartie
 *   sur 10 000 tantièmes (millièmes de copropriété).
 * - Coordonnées bancaires dédiées : Conformément à l'article 26 de la Loi 18-00,
 *   obligation légale d'ouverture d'un compte bancaire séparé au nom exclusif
 *   du "Syndicat des Copropriétaires", distinct du patrimoine personnel du syndic.
 * - Charte et Règlement de Copropriété : Rappel des obligations de bon voisinage,
 *   respect des horaires de tranquillité et usage des parties communes.
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DES DONNÉES D'IDENTITÉ ET DU PATRIMOINE
// ============================================================================

/**
 * GUID unique du tenant actif résolu depuis la requête ou le cookie de session.
 *
 * @var string $guid
 */
$guid = $guid ?? TenantDB::resolveGuid();

/**
 * Enregistrement des paramètres généraux et légaux de la résidence.
 *
 * @var array<string, mixed> $residence
 */
$residence = $residence ?? TenantDB::getResidence();

/**
 * Nombre total de lots composant l'immeuble ou ensemble immobilier.
 *
 * @var int $lotsCount
 */
$lotsCount = count(TenantDB::getLots());
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DE LA VUE RÉSIDENT : IDENTITÉ DE L'IMMEUBLE           -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- En-tête de page avec code unique d'identification cadastrale / système -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Mon Immeuble & Mon Syndic
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Fiche d'identité juridique, mandat du syndic et charte de bon voisinage de la copropriété
            </p>
        </div>

        <div class="px-3.5 py-1.5 rounded-2xl bg-[#D91C6E]/10 border border-[#D91C6E]/20 text-[#D91C6E] dark:text-[#F26968] font-bold text-xs">
            Code Résidence : <?= htmlspecialchars($residence['code_unique'] ?? 'COP-001') ?>
        </div>
    </div>

    <!-- Grille responsive à 2 colonnes regroupant les 4 cartes institutionnelles -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ================================================================= -->
        <!-- CARTE 1 : FICHE D'IDENTITÉ JURIDIQUE DE L'IMMEUBLE                -->
        <!-- ================================================================= -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-sm">
                    🏢
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Identité de la Résidence</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Renseignements légaux sous la Loi 18-00</p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <!-- Dénomination légale de la copropriété -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Nom de la Copropriété</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($residence['nom']) ?></span>
                </div>

                <!-- Ville d'implantation -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Ville & Localisation</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($residence['ville']) ?></span>
                </div>

                <!-- Adresse physique complète -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Adresse Complète</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($residence['adresse']) ?></span>
                </div>

                <!-- Numéro du Titre Foncier Global (Conservation Foncière) -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Titre Foncier Mère</span>
                    <span class="font-mono font-bold text-[#D91C6E] dark:text-[#F26968]"><?= htmlspecialchars($residence['titre_foncier_mere'] ?: 'En cours d\'immatriculation') ?></span>
                </div>

                <!-- Nombre de fractions privatives / lots cadastraux -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Nombre de Lots Enregistrés</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= $lotsCount ?> Lots</span>
                </div>

                <!-- Total conventionnel des quotes-parts de copropriété -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Total Tantièmes</span>
                    <span class="font-bold text-slate-900 dark:text-white">10 000 Tantièmes</span>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CARTE 2 : MANDAT DU SYNDIC & COORDONNÉES DU MANDATAIRE            -->
        <!-- ================================================================= -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center font-bold text-sm">
                    👤
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Syndic en Exercice</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Élu démocratiquement par l'Assemblée Générale</p>
                </div>
            </div>

            <div class="space-y-3 text-xs">
                <!-- Identité du syndic élu (bénévole ou professionnel) -->
                <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2">
                    <div class="text-[10px] font-bold text-[#D91C6E] uppercase">Représentant Légal</div>
                    <div class="text-base font-bold text-slate-900 dark:text-white">
                        <?= htmlspecialchars($residence['nom_syndic'] ?? 'Syndic Élu') ?>
                    </div>
                    <p class="text-slate-500 text-[11px]">
                        Mandataire légal du Syndicat des Copropriétaires selon le Dahir n° 1-02-298 (Loi 18-00).
                    </p>
                </div>

                <!-- Courriel officiel pour correspondances administratives -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                    <span class="text-slate-500">Email Officiel</span>
                    <a href="mailto:<?= htmlspecialchars($residence['email_syndic']) ?>" class="font-bold text-[#D91C6E] dark:text-[#F26968] hover:underline">
                        <?= htmlspecialchars($residence['email_syndic']) ?>
                    </a>
                </div>

                <!-- Téléphone direct en cas de signalement -->
                <?php if (! empty($residence['tel_syndic'])) { ?>
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                        <span class="text-slate-500">Téléphone Direct</span>
                        <a href="tel:<?= htmlspecialchars($residence['tel_syndic']) ?>" class="font-bold text-slate-900 dark:text-white hover:underline">
                            <?= htmlspecialchars($residence['tel_syndic']) ?>
                        </a>
                    </div>
                <?php } ?>

                <!-- Horaires de permanence du bureau -->
                <div class="p-3.5 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-1 text-[11px]">
                    <div class="font-bold text-slate-800 dark:text-slate-200">Permanence & Réception</div>
                    <div class="text-slate-500">
                        Le bureau du syndic tient une permanence hebdomadaire sur rendez-vous pour toute question relative aux comptes, travaux ou règlements.
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CARTE 3 : COORDONNÉES BANCAIRES DÉDIÉES (RIB OFFICIEL)            -->
        <!-- ================================================================= -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-sm">
                    🏦
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Compte Bancaire Dédié</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Compte ouvert au nom exclusif du Syndicat des Copropriétaires</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-3 text-xs">
                <!-- Nom de la banque dépositaire -->
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Établissement Bancaire</div>
                    <div class="font-bold text-slate-900 dark:text-white mt-0.5"><?= htmlspecialchars($residence['banque_nom'] ?? 'Banque Partenaire') ?></div>
                </div>

                <!-- RIB normalisé 24 chiffres (Banque, Guichet, Compte, Clé) -->
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase">Relevé d'Identité Bancaire (RIB 24 chiffres)</div>
                    <div class="p-3 rounded-xl bg-white dark:bg-[#22082E] border border-[#F0E4DC] dark:border-[#3D154F] font-mono font-bold text-[#D91C6E] dark:text-[#F26968] text-sm break-all select-all mt-1">
                        <?= htmlspecialchars($residence['rib_bancaire'] ?? '000 000 0000000000000000 00') ?>
                    </div>
                </div>

                <!-- Instructions de libellé de virement -->
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-[#250832] border border-slate-200 dark:border-[#3D154F] text-[11px] text-slate-600 dark:text-slate-400">
                    💡 <em>Tout virement doit mentionner votre numéro de lot dans le motif. La quittance est émise dès validation du relevé bancaire.</em>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- CARTE 4 : CHARTE DE BON VOISINAGE ET EXTRAITS DU RÈGLEMENT        -->
        <!-- ================================================================= -->
        <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <div class="flex items-center gap-2.5 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center font-bold text-sm">
                    🤝
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Règles Communes & Voisinage</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Extraits du Règlement de Copropriété</p>
                </div>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                <!-- Règle 1 : Bruit et tranquillité -->
                <div class="p-2.5 rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] flex items-start gap-2">
                    <span aria-hidden="true">🔇</span>
                    <div><strong>Tranquillité sonore :</strong> Éviter tout bruit excessif entre 22h00 et 08h00 du matin. Travaux autorisés du lundi au vendredi (09h00-18h00) et samedi matin.</div>
                </div>

                <!-- Règle 2 : Respect des stationnements et places privatives -->
                <div class="p-2.5 rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] flex items-start gap-2">
                    <span aria-hidden="true">🚗</span>
                    <div><strong>Stationnement & Parkings :</strong> Stationner exclusivement dans votre place numérotée désignée sans empiéter sur les voies d'accès ou places visiteurs.</div>
                </div>

                <!-- Règle 3 : Hygiène et gestion des ordures -->
                <div class="p-2.5 rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] flex items-start gap-2">
                    <span aria-hidden="true">🗑️</span>
                    <div><strong>Propreté & Déchets :</strong> Déposer les ordures ménagères dans des sacs hermétiques fermés aux emplacements prévus au sous-sol. Ne rien entreposer dans les paliers.</div>
                </div>

                <!-- Règle 4 : Usage précautionneux des équipements collectifs -->
                <div class="p-2.5 rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] flex items-start gap-2">
                    <span aria-hidden="true">🛗</span>
                    <div><strong>Utilisation des Ascenseurs :</strong> Interdiction de surcharger les cabines d'ascenseur lors des déménagements ou transports de matériaux sans protection.</div>
                </div>
            </div>
        </div>
    </div>
</div>

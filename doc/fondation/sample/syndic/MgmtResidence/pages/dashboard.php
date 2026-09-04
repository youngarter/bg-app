<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/dashboard.php
 * TYPE           : Vue Métier / Cockpit de Pilotage Financier & Opérationnel
 * MODULE         : Tableau de Bord Exécutif, KPIs & Amorçage Copropriété
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 18 : Administration et conservation de l'immeuble
 *                  - Fonds de réserve obligatoire pour travaux
 *                  - Reddition des comptes et transparence financière
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Cette vue offre au Syndic Administrateur et aux membres autorisés du bureau
 * une vision synthétique et immédiate de la santé financière et technique
 * de la copropriété pour l'exercice comptable actif.
 *
 * Composants fonctionnels majeurs :
 * 1. Détection & Guide d'Amorçage Greenfield ($isGreenfield) :
 *    - Pour une nouvelle résidence vierge, guide pas-à-pas en 4 jalons légaux :
 *      a. Configuration des lots et quote-parts de tantièmes (Art. 36-37).
 *      b. Enregistrement des copropriétaires et génération des identifiants résidents.
 *      c. Tenue de l'Assemblée Générale constitutive et vote du budget prévisionnel.
 *      d. Émission du premier appel de fonds périodique exigible.
 * 2. 4 Indicateurs Financiers Clés (Cockpit KPIs) :
 *    - Trésorerie Disponible : Solde consolidé Banque (RIB) + Caisse espèces.
 *    - Taux de Recouvrement (%) : Ratio cotisations encaissées / total appelé.
 *    - Cumul des Impayés : Total des arriérés débiteurs en attente de relance.
 *    - Réserve Fonds de Travaux : Épargne obligatoire d'anticipation (Art. 18).
 * 3. Flux d'Activités Récentes :
 *    - 5 derniers encaissements avec accès direct aux quittances libératoires.
 *    - 5 dernières factures de prestataires imputées sur les charges communes.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// EXTRACTION DES AGRÉGATS COMPTABLES DU TENANT POUR L'EXERCICE
// ----------------------------------------------------------------------------
// Exercice comptable sélectionné (année civile active par défaut).
$ex = (int) ($selectedExercice ?? date('Y'));

// Synthèse financière globale consolidée (banque, caisse, appels, encaissements, impayés).
$cockpit = TenantDB::getFinancialCockpit($ex);

// Récupération des 5 derniers règlements pour affichage dans le flux d'activité.
$paiements = array_slice(TenantDB::getPaiements($ex), 0, 5);

// Récupération des 5 dernières dépenses imputées sur l'exercice.
$depenses = array_slice(TenantDB::getDepenses($ex), 0, 5);

// Chargement des données structurelles pour évaluer le niveau d'initialisation (Greenfield).
$lots = TenantDB::getLots();
$coproprietaires = TenantDB::getCoproprietaires();
$assemblees = TenantDB::getAssemblees();
$appels = TenantDB::getAppels($ex);

// Indicateurs booléens de complétude de la configuration de l'immeuble.
$hasLots = count($lots) > 0;
$hasCoproprietaires = count($coproprietaires) > 0;
$hasAssemblees = count($assemblees) > 0;
$hasAppels = count($appels) > 0;

// Une copropriété est dite 'greenfield' si l'un au moins des 4 piliers fondateurs manque.
$isGreenfield = (! $hasLots || ! $hasCoproprietaires || ! $hasAssemblees || ! $hasAppels);
?>

<div class="space-y-6">
    <!-- En-tête exécutif avec lien direct vers les Annexes Comptables Légales -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Synthèse Financière & Gestion
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Exercice <?= $ex ?> &bull; <?= htmlspecialchars($residence['nom']) ?> (<?= count($lots) ?> lots gérés selon la Loi 18-00)
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="index.php?tenant=<?= urlencode($guid) ?>&page=annexes"
                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
            >
                <span>📜 Générer Annexes 1 à 5</span>
            </a>
        </div>
    </div>

    <!-- BANNIÈRE D'AMORÇAGE POUR NOUVEAU TENANT VIERGE (GREENFIELD ONBOARDING) -->
    <?php if ($isGreenfield) { ?>
        <div class="p-5 rounded-3xl bg-gradient-to-br from-blue-900/10 via-purple-900/10 to-pink-900/10 dark:from-[#1E0427]/60 dark:via-[#2b0c38]/40 dark:to-[#131927] border border-blue-200 dark:border-purple-800/50 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        🚀
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                            Amorçage & Configuration Initiale de la Copropriété
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">
                            Guide étape par étape conforme à la Loi 18-00 pour initialiser votre résidence
                        </p>
                    </div>
                </div>
                <!-- Jauge de progression calculée sur les 4 étapes fondamentales -->
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 font-mono">
                    <?= ($hasLots ? 1 : 0) + ($hasCoproprietaires ? 1 : 0) + ($hasAssemblees ? 1 : 0) + ($hasAppels ? 1 : 0) ?> / 4 Étapes
                </span>
            </div>

            <!-- Grille interactive des 4 étapes de démarrage -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <!-- Étape 1 : Paramétrage des Lots et Tantièmes -->
                <a
                    href="index.php?tenant=<?= urlencode($guid) ?>&page=lots"
                    class="p-4 rounded-2xl border transition block <?= $hasLots ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 dark:text-emerald-200' : 'bg-white dark:bg-[#131927] border-slate-200 dark:border-slate-800 hover:border-blue-500' ?>"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-xs uppercase tracking-wider <?= $hasLots ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' ?>">
                            Étape 1 &bull; Lots
                        </span>
                        <span class="text-sm"><?= $hasLots ? '✅' : '🏢' ?></span>
                    </div>
                    <div class="font-bold text-slate-900 dark:text-white">Configurer les Lots</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        <?= $hasLots ? (count($lots).' lot(s) configuré(s)') : 'Saisir appartements & tantièmes' ?>
                    </p>
                </a>

                <!-- Étape 2 : Registre des Copropriétaires -->
                <a
                    href="index.php?tenant=<?= urlencode($guid) ?>&page=coproprietaires"
                    class="p-4 rounded-2xl border transition block <?= $hasCoproprietaires ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 dark:text-emerald-200' : 'bg-white dark:bg-[#131927] border-slate-200 dark:border-slate-800 hover:border-blue-500' ?>"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-xs uppercase tracking-wider <?= $hasCoproprietaires ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' ?>">
                            Étape 2 &bull; Résidents
                        </span>
                        <span class="text-sm"><?= $hasCoproprietaires ? '✅' : '👥' ?></span>
                    </div>
                    <div class="font-bold text-slate-900 dark:text-white">Ajouter les Copropriétaires</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        <?= $hasCoproprietaires ? (count($coproprietaires).' copropriétaire(s)') : 'Affecter aux lots & créer les accès' ?>
                    </p>
                </a>

                <!-- Étape 3 : Assemblée Générale & Budget Annuel Prévisionnel -->
                <a
                    href="index.php?tenant=<?= urlencode($guid) ?>&page=assemblees"
                    class="p-4 rounded-2xl border transition block <?= $hasAssemblees ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 dark:text-emerald-200' : 'bg-white dark:bg-[#131927] border-slate-200 dark:border-slate-800 hover:border-blue-500' ?>"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-xs uppercase tracking-wider <?= $hasAssemblees ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' ?>">
                            Étape 3 &bull; Budget AG
                        </span>
                        <span class="text-sm"><?= $hasAssemblees ? '✅' : '🗳️' ?></span>
                    </div>
                    <div class="font-bold text-slate-900 dark:text-white">Enregistrer l'AG & Budget</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        <?= $hasAssemblees ? (count($assemblees).' assemblée(s)') : 'Voter le budget prévisionnel annuel' ?>
                    </p>
                </a>

                <!-- Étape 4 : Émission des Appels de Fonds -->
                <a
                    href="index.php?tenant=<?= urlencode($guid) ?>&page=appels"
                    class="p-4 rounded-2xl border transition block <?= $hasAppels ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 dark:text-emerald-200' : 'bg-white dark:bg-[#131927] border-slate-200 dark:border-slate-800 hover:border-blue-500' ?>"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-xs uppercase tracking-wider <?= $hasAppels ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500' ?>">
                            Étape 4 &bull; Appels
                        </span>
                        <span class="text-sm"><?= $hasAppels ? '✅' : '📢' ?></span>
                    </div>
                    <div class="font-bold text-slate-900 dark:text-white">Émettre l'Appel de Fonds</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        <?= $hasAppels ? (count($appels).' appel(s) émis') : 'Calculer les cotisations exigibles' ?>
                    </p>
                </a>
            </div>
        </div>
    <?php } ?>

    <!-- 4 CARTES MÉTRIQUES EXÉCUTIVES (COCKPIT KPIS) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1 : Trésorerie Nette Disponible (Banque + Caisse) -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-2 transition-colors">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span class="font-semibold">Trésorerie Disponible</span>
                <span class="text-blue-600 dark:text-blue-400">🏦</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= formatMAD($cockpit['tresorerieDisponible']) ?></div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                <span>Banque : <strong class="text-slate-800 dark:text-slate-200"><?= formatMAD($cockpit['soldeBanque']) ?></strong></span>
                <span>Caisse : <strong class="text-slate-800 dark:text-slate-200"><?= formatMAD($cockpit['soldeCaisse']) ?></strong></span>
            </div>
        </div>

        <!-- KPI 2 : Taux d'Encaissement des Charges Votées -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-2 transition-colors">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span class="font-semibold">Taux de Recouvrement</span>
                <span class="text-emerald-600 dark:text-emerald-400">📈</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400"><?= $cockpit['tauxRecouvrement'] ?>%</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                <span>Encaissé : <strong class="text-slate-800 dark:text-slate-200"><?= formatMAD($cockpit['totalEncaisse']) ?></strong></span>
                <span>Appelé : <strong class="text-slate-800 dark:text-slate-200"><?= formatMAD($cockpit['totalAppele']) ?></strong></span>
            </div>
        </div>

        <!-- KPI 3 : Cumul des Impayés et Créances Restantes -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-2 transition-colors">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span class="font-semibold">Total des Impayés</span>
                <span class="text-rose-600 dark:text-rose-400">⚠️</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400"><?= formatMAD($cockpit['totalImpayes']) ?></div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                <span>Charges en attente</span>
                <a href="index.php?tenant=<?= urlencode($guid) ?>&page=relances" class="text-rose-600 dark:text-rose-400 hover:underline font-semibold">Relances &rarr;</a>
            </div>
        </div>

        <!-- KPI 4 : Réserve du Fonds de Travaux Obligatoire (Art. 18) -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-2 transition-colors">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span class="font-semibold">Réserve Fonds Travaux</span>
                <span class="text-amber-500">🛡️</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-300"><?= formatMAD($cockpit['fondTravaux']) ?></div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800">
                <span>Obligatoire Art. 18 Loi 18-00</span>
            </div>
        </div>
    </div>

    <!-- DOUBLE PANNEAU : FLUX DES ACTIVITÉS RÉCENTES (ENCAISSEMENTS & FACTURES) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Volet Gauche : 5 Derniers Règlements Reçus -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🧾</span>
                    <span>Derniers Encaissements (<?= $ex ?>)</span>
                </h3>
                <a href="index.php?tenant=<?= urlencode($guid) ?>&page=paiements" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold">
                    Tout voir &rarr;
                </a>
            </div>

            <div class="space-y-2">
                <?php if (empty($paiements)) { ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Aucun encaissement enregistré pour cet exercice.</p>
                <?php } else { ?>
                    <?php foreach ($paiements as $p) { ?>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars(($p['coproprietaire_prenom'] ?? '').' '.($p['coproprietaire_nom'] ?? 'Copropriétaire')) ?>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Lot <?= htmlspecialchars($p['lot_numero'] ?? 'N/A') ?> &bull; <?= formatDateFR($p['date_paiement']) ?> &bull; <?= htmlspecialchars(MODE_PAIEMENT_LABELS[$p['mode_paiement']] ?? $p['mode_paiement']) ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400"><?= formatMAD($p['montant']) ?></span>
                                <!-- Bouton de téléchargement de la quittance libératoire -->
                                <a
                                    href="quittance.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode($p['id']) ?>"
                                    target="_blank"
                                    class="p-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                    title="Télécharger Quittance"
                                >
                                    📥
                                </a>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <!-- Volet Droit : 5 Dernières Dépenses et Factures Imputées -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💸</span>
                    <span>Dernières Dépenses & Factures</span>
                </h3>
                <a href="index.php?tenant=<?= urlencode($guid) ?>&page=depenses" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold">
                    Tout voir &rarr;
                </a>
            </div>

            <div class="space-y-2">
                <?php if (empty($depenses)) { ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Aucune dépense enregistrée pour cet exercice.</p>
                <?php } else { ?>
                    <?php foreach ($depenses as $d) { ?>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($d['fournisseur_nom']) ?></div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400"><?= htmlspecialchars($d['description']) ?> &bull; <?= formatDateFR($d['date']) ?></div>
                            </div>
                            <span class="font-bold text-slate-800 dark:text-slate-200"><?= formatMAD($d['montant_ttc']) ?></span>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

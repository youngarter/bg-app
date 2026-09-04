<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/annexes.php
 * TYPE           : Vue Métier / Documents Comptables Réglementaires
 * MODULE         : Gouvernance, Clôture d'Exercice & États Financiers Annuels
 * CADRE JURIDIQUE: Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002)
 *                  promulguant la Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 18 & 24 : Reddition obligatoire des comptes en AG
 *                  - Annexe 1        : État de situation financière & trésorerie
 *                  - Annexe 2        : Compte de gestion général des charges courantes
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Cette page met en page et calcule les états comptables obligatoires présentés
 * aux copropriétaires lors de l'Assemblée Générale annuelle pour approbation
 * des comptes et vote du quitus au syndic.
 *
 * États certifiés :
 * 1. Annexe 1 - Tableau d'Équilibre Financier (Bilan Simplifié) :
 *    - Actif : Compte bancaire séparé (RIB syndicat), encaisse liquide (caisse),
 *      et créances exigibles sur copropriétaires (impayés d'appels de charges).
 *    - Passif : Fonds de réserve travaux obligatoire (Art. 18 Loi 18-00),
 *      provisions pour charges courantes encaissées, et trésorerie nette disponible.
 * 2. Annexe 2 - Compte de Gestion des Dépenses de l'Exercice :
 *    - Ventilation des dépenses réelles par catégories comptables (ascenseur,
 *      gardiennage, électricité, nettoyage, etc.).
 * 3. Formalisme Légal d'Émargement :
 *    - Date, ville, et cartouche officiel réservé à la signature et au cachet du syndic.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// EXTRACTION DES DONNÉES COMPTABLES DU COCKPIT ET DE L'EXERCICE
// ----------------------------------------------------------------------------
// Exercice comptable sélectionné (année civile courante par défaut).
$ex = (int) ($selectedExercice ?? date('Y'));

// Cockpit financier consolidé (trésorerie, impayés, dépenses totales, fonds de réserve).
$cockpit = TenantDB::getFinancialCockpit($ex);

// Fiche signalétique de la résidence (nom, ville, syndic en exercice).
$res = TenantDB::getResidence();

// Liste exhaustive des factures et dépenses de l'exercice pour ventilation.
$depenses = TenantDB::getDepenses($ex);

// Liste des paiements perçus pour l'exercice.
$paiements = TenantDB::getPaiements($ex);

// Liste des lots pour référence des tantièmes.
$lots = TenantDB::getLots();
?>

<div class="space-y-6">
    <!-- En-tête de section avec bouton d'impression des annexes officielles -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Annexes Légales Obligatoires (Loi 18-00)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Documents comptables et financiers légaux prévus par le Dahir n° 1-02-298 pour l'Assemblée Générale
            </p>
        </div>
        <button
            onclick="window.print()"
            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-sm"
        >
            <span>🖨️ Imprimer les Annexes Légales</span>
        </button>
    </div>

    <!-- ANNEXE 1 : SITUATION FINANCIÈRE ET TRÉSORERIE DE LA COPROPRIÉTÉ -->
    <div class="p-6 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">ANNEXE 1 : Tableau de Situation Financière & Trésorerie</h3>
                <p class="text-[11px] text-slate-500">Arrêté au 31 Décembre <?= $ex ?> &bull; <?= htmlspecialchars($res['nom']) ?></p>
            </div>
            <span class="px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 font-mono text-xs font-bold">
                Exercice <?= $ex ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
            <!-- Bloc Actif : Trésorerie existante et créances exigibles -->
            <div class="space-y-2">
                <div class="font-bold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">Actif (Trésorerie & Créances)</div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-2">
                    <div class="flex justify-between">
                        <span>Compte Bancaire Syndicat (RIB) :</span>
                        <strong class="font-mono text-slate-900 dark:text-white"><?= formatMAD($cockpit['soldeBanque']) ?></strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Caisse Espèces Syndic :</span>
                        <strong class="font-mono text-slate-900 dark:text-white"><?= formatMAD($cockpit['soldeCaisse']) ?></strong>
                    </div>
                    <div class="flex justify-between text-rose-600 dark:text-rose-400 font-semibold border-t border-slate-200 dark:border-slate-800 pt-1">
                        <span>Créances sur Copropriétaires (Impayés) :</span>
                        <strong class="font-mono"><?= formatMAD($cockpit['totalImpayes']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Bloc Passif : Dettes, provisions et réserves de travaux -->
            <div class="space-y-2">
                <div class="font-bold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">Passif (Fonds Réservés & Dettes)</div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-2">
                    <div class="flex justify-between text-amber-600 dark:text-amber-400 font-semibold">
                        <span>Fonds de Travaux Obligatoire (Art. 18) :</span>
                        <strong class="font-mono"><?= formatMAD($cockpit['fondTravaux']) ?></strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Provisions sur charges courantes :</span>
                        <strong class="font-mono text-slate-900 dark:text-white"><?= formatMAD($cockpit['totalEncaisse']) ?></strong>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 dark:border-slate-800 pt-1 font-bold text-slate-900 dark:text-white">
                        <span>Trésorerie Nette Disponible :</span>
                        <strong class="font-mono text-emerald-600 dark:text-emerald-400"><?= formatMAD($cockpit['tresorerieDisponible']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ANNEXE 2 : COMPTE DE GESTION GÉNÉRAL DES CHARGES COURANTES -->
    <div class="p-6 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">ANNEXE 2 : Compte de Gestion Général des Charges Courantes</h3>
                <p class="text-[11px] text-slate-500">Récapitulatif des produits et des charges de l'exercice <?= $ex ?></p>
            </div>
        </div>

        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-500">
                    <tr>
                        <th class="p-2.5">Poste de Dépense</th>
                        <th class="p-2.5 text-right">Montant Réalisé (TTC)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php
                        // Ventilation et regroupement des montants TTC par catégories de charges
                        $grouped = [];
foreach ($depenses as $d) {
    $cat = $d['categorie'] ?: 'Autres';
    $grouped[$cat] = ($grouped[$cat] ?? 0) + $d['montant_ttc'];
}
?>
                    <?php if (empty($grouped)) { ?>
                        <tr><td colspan="2" class="p-4 text-center text-slate-400">Aucune charge comptabilisée pour cet exercice.</td></tr>
                    <?php } else { ?>
                        <?php foreach ($grouped as $cat => $tot) { ?>
                            <tr>
                                <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200"><?= htmlspecialchars($cat) ?></td>
                                <td class="p-2.5 text-right font-mono font-bold"><?= formatMAD($tot) ?></td>
                            </tr>
                        <?php } ?>
                        <!-- Ligne de total général conforme aux écritures du grand livre -->
                        <tr class="bg-slate-50 dark:bg-slate-950 font-bold">
                            <td class="p-2.5 text-slate-900 dark:text-white">TOTAL GÉNÉRAL DES CHARGES :</td>
                            <td class="p-2.5 text-right font-mono text-blue-600 dark:text-blue-400"><?= formatMAD($cockpit['totalDepenses']) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MENTIONS LÉGALES DE CERTIFICATION ET D'ÉMARGEMENT DU SYNDIC -->
    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 flex justify-between items-center">
        <div>
            Document certifié conforme par le Syndic en exercice : <strong><?= htmlspecialchars($res['nom_syndic']) ?></strong><br>
            Fait à <?= htmlspecialchars($res['ville']) ?>, le <?= date('d/m/Y') ?> &bull; Mandat Loi 18-00
        </div>
        <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 px-6 py-4 rounded-xl text-center">
            Signature et Cachet du Syndic
        </div>
    </div>
</div>

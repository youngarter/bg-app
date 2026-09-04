<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/projets.php
 * TYPE           : Vue Métier / Suivi des Grands Projets & Travaux Exceptionnels
 * MODULE         : Investissements, Rénovations & Fonds de Réserve Travaux
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 18 : Décisions de travaux en Assemblée Générale
 *                  - Suivi d'exécution du budget extraordinaire voté
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module offre une vue d'ensemble sur les chantiers de rénovation,
 * d'embellissement ou de réparation majeure votés par l'Assemblée Générale.
 *
 * Paramètres suivis :
 * 1. Calendrier d'Exécution :
 *    - Date de démarrage des travaux et échéance contractuelle prévisionnelle.
 * 2. Contrôle Budgétaire :
 *    - Budget global voté et alloué au chantier (en Dirhams Marocains MAD).
 * 3. Statut Opérationnel :
 *    - Cycle d'avancement : planifié, en cours, livré ou réceptionné.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES CHANTIERS ET PROJETS DU TENANT
// ----------------------------------------------------------------------------
// Extraction de la liste des projets inscrits au plan pluriannuel ou votés en AG.
$projets = TenantDB::getProjets();
?>

<div class="space-y-6">
    <!-- En-tête de section avec sous-titre de gouvernance -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Projets & Grands Travaux Votés</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Suivi des chantiers de rénovation, ravalement et interventions extraordinaires (Art. 18 Loi 18-00)</p>
        </div>
    </div>

    <!-- Grille des chantiers et travaux -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if (empty($projets)) { ?>
            <!-- État affiché lorsqu'aucun grand projet n'est programmé -->
            <div class="col-span-2 p-8 text-center text-slate-400">Aucun grand chantier en cours.</div>
        <?php } else { ?>
            <!-- Boucle de restitution des fiches projets -->
            <?php foreach ($projets as $p) { ?>
                <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($p['titre']) ?></h3>
                            <p class="text-[11px] text-slate-400">Début : <?= formatDateFR($p['date_debut']) ?> &bull; Fin prévue : <?= formatDateFR($p['date_fin_prevue']) ?></p>
                        </div>
                        <!-- Badge du statut opérationnel du chantier -->
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-blue-500/10 text-blue-600 border border-blue-500/20">
                            <?= strtoupper($p['statut']) ?>
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-300"><?= htmlspecialchars($p['description']) ?></p>

                    <!-- Ligne budgétaire votée en Assemblée Générale -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Budget Voté :</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white"><?= formatMAD($p['budget_estime']) ?></span>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/fournisseurs.php
 * TYPE           : Vue Métier / Annuaire Prestataires & Contrats Tiers
 * MODULE         : Fournisseurs, Contrats d'Entretien & Coordonnées Professionnelles
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Gestion des contrats de maintenance des parties communes
 *                  - Identification légale des sociétés (ICE marocain)
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module présente le carnet d'adresses professionnel et contractuel des
 * prestataires de services intervenant au sein de la copropriété.
 *
 * Éléments gérés :
 * 1. Métiers et Corps d'État :
 *    - Maintenance des ascenseurs, sécurité/gardiennage, nettoyage, électricité,
 *      plomberie, espaces verts et assurances multirisques immeuble.
 * 2. Données Contractuelles et Légales :
 *    - Identifiant Commun de l'Entreprise (ICE) garantissant la régularité fiscale.
 *    - Coordonnées téléphoniques d'urgence et adresses emails directes.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DU RÉPERTOIRE DES PRESTATAIRES
// ----------------------------------------------------------------------------
// Chargement de l'ensemble des prestataires référencés pour ce tenant.
$fournisseurs = TenantDB::getFournisseurs();
?>

<div class="space-y-6">
    <!-- En-tête de section avec sous-titre explicatif -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Répertoire des Prestataires & Fournisseurs</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Contrats de maintenance (ascenseur, gardiennage, nettoyage, pompes, assurances)</p>
        </div>
    </div>

    <!-- Grille des cartes prestataires -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if (empty($fournisseurs)) { ?>
            <!-- État vide si aucun fournisseur n'est encore enregistré -->
            <div class="col-span-3 p-8 text-center text-slate-400">Aucun prestataire enregistré dans la base.</div>
        <?php } else { ?>
            <!-- Itération et affichage des fiches prestataires -->
            <?php foreach ($fournisseurs as $f) { ?>
                <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($f['nom']) ?></h3>
                        <p class="text-xs text-blue-600 dark:text-blue-400 font-medium"><?= htmlspecialchars($f['activite']) ?></p>
                    </div>

                    <div class="text-xs text-slate-600 dark:text-slate-400 space-y-1 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div>📞 <?= htmlspecialchars($f['telephone'] ?: 'N/A') ?></div>
                        <div>✉️ <?= htmlspecialchars($f['email'] ?: 'N/A') ?></div>
                        <?php if ($f['ice']) { ?>
                            <div class="font-mono text-[10px] text-slate-400">ICE : <?= htmlspecialchars($f['ice']) ?></div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

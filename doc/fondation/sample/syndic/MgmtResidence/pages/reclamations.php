<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/reclamations.php
 * TYPE           : Vue Métier / Registre des Incidents, Pannes & Réclamations
 * MODULE         : Relation Résidents, Maintenance & Signalements Techniques
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Devoir de diligence et d'intervention du Syndic
 *                  - Traçabilité des signalements des parties communes
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module centralise les signalements d'incidents formulés par les occupants
 * ou le syndic concernant les équipements et parties communes de l'immeuble.
 *
 * Fonctionnalités et suivi :
 * 1. Priorisation des Incidents :
 *    - Niveaux d'urgence : Urgente (fuite majeure, panne ascenseur), Normale, Faible.
 * 2. Traçabilité des Échanges :
 *    - Identification de l'auteur du signalement et date de notification.
 *    - Zone de réponse et plan d'action formalisé par le Syndic.
 * 3. Modale de Saisie d'Incident :
 *    - Formulaire d'enregistrement direct transmettant les informations
 *      au contrôleur d'action `actions/add_reclamation.php`.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES RÉCLAMATIONS ET CONTRÔLE DE LICENCE
// ----------------------------------------------------------------------------
// Chargement de l'ensemble des tickets d'incidents enregistrés pour la copropriété.
$reclamations = TenantDB::getReclamations();

// Vérification de l'état de la licence commerciale (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6">
    <!-- En-tête de section avec bouton d'ouverture de signalement -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Tickets & Réclamations Résidents</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Signalements des pannes, nuisances et demandes d'intervention</p>
        </div>

        <!-- Contrôle de l'action de création de ticket -->
        <?php if (! $isReadOnly) { ?>
            <button
                onclick="document.getElementById('modal-add-rec')?.classList.remove('hidden')"
                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
            >
                <span>➕ Déposer une Réclamation</span>
            </button>
        <?php } else { ?>
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                <span>🔒</span>
                <span>Signalements désactivés (Lecture seule)</span>
            </span>
        <?php } ?>
    </div>

    <!-- Grille des fiches d'incidents -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if (empty($reclamations)) { ?>
            <!-- État vide lorsqu'aucun incident n'est en cours -->
            <div class="col-span-2 p-8 text-center text-slate-400">Aucune réclamation ouverte pour cette copropriété.</div>
        <?php } else { ?>
            <!-- Boucle de restitution des réclamations -->
            <?php foreach ($reclamations as $r) { ?>
                <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($r['titre']) ?></h3>
                            <p class="text-[11px] text-slate-400">Signalé par <?= htmlspecialchars($r['auteur'] ?: 'Résident') ?> &bull; <?= formatDateFR($r['date_creation']) ?></p>
                        </div>
                        <!-- Badge du niveau d'urgence -->
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase <?= $r['priorite'] === 'urgente' ? 'bg-red-500/10 text-red-600 border border-red-500/20' : 'bg-blue-500/10 text-blue-600 border border-blue-500/20' ?>">
                            <?= strtoupper($r['priorite']) ?>
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-300"><?= htmlspecialchars($r['description']) ?></p>

                    <!-- Bloc de prise en charge et réponse du syndic -->
                    <?php if (! empty($r['reponse_syndic'])) { ?>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300 block text-[10px]">Réponse du Syndic :</span>
                            <span class="text-slate-600 dark:text-slate-400"><?= htmlspecialchars($r['reponse_syndic']) ?></span>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<!-- MODALE D'ENREGISTREMENT D'UN SIGNALEMENT (ACCESSIBLE SI NON LECTURE SEULE) -->
<?php if (! $isReadOnly) { ?>
<div id="modal-add-rec" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
    <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <h3 class="font-bold text-sm">Déposer un Signalement / Réclamation</h3>
            <button onclick="document.getElementById('modal-add-rec').classList.add('hidden')" class="text-slate-400">&times;</button>
        </div>
        <form action="actions/add_reclamation.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
            <div>
                <label class="block font-semibold mb-1">Titre de la Panne ou Demande *</label>
                <input type="text" name="titre" required placeholder="Ex: Fuite d'eau cage d'escalier B" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold">
            </div>
            <div>
                <label class="block font-semibold mb-1">Niveau d'Urgence</label>
                <select name="priorite" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                    <option value="normale">Normale</option>
                    <option value="urgente">Urgente (Intervention rapide)</option>
                    <option value="faible">Information</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold mb-1">Description Détaillée *</label>
                <textarea name="description" rows="3" required placeholder="Décrivez la panne constatée..." class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl"></textarea>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-add-rec').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-zinc-900 rounded-xl">Annuler</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-bold rounded-xl shadow">Transmettre au Syndic</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

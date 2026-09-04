<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/coproprietaires.php
 * TYPE           : Vue Métier / Registre des Membres du Syndicat
 * MODULE         : État Civil, Lots Détenus, Droits de Vote & Comptes Portails
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 11 : Constitution du Syndicat des Copropriétaires
 *                  - Convocation et émargement des assemblées générales
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module gère l'annuaire exhaustif des copropriétaires de l'immeuble.
 *
 * Attributs et processus :
 * 1. État Civil & Données Légales :
 *    - Nom, Prénom, Civilité, Numéro de CIN (Carte d'Identité Nationale).
 *    - Statut d'occupation : Copropriétaire Résident (occupant) ou Bailleur.
 * 2. Consolidation Foncière :
 *    - Calcul dynamique du nombre de lots rattachés et de la somme des tantos
 *      détenus (puissance de vote aux assemblées générales sur 10 000).
 * 3. Identifiants d'Accès Résident :
 *    - Affichage de l'identifiant personnalisé de connexion au portail privatif
 *      (format `prenom.nom@tag` ou email).
 * 4. Création avec Modale Sécurisée :
 *    - Formulaire d'enregistrement avec blocage en mode lecture seule.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES MEMBRES DU SYNDICAT ET DES LOTS ASSOCIÉS
// ----------------------------------------------------------------------------
// Chargement de l'ensemble des copropriétaires enregistrés dans la base dédiée.
$coproprietaires = TenantDB::getCoproprietaires();

// Chargement de tous les lots pour calcul en mémoire des tantièmes cumulés.
$lots = TenantDB::getLots();

// Vérification de la licence commerciale du tenant (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6">
    <!-- En-tête de section avec bouton d'ajout de copropriétaire -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Registre des Copropriétaires</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Coordonnées, CIN, tantièmes détenus et comptes d'accès à l'espace résident</p>
        </div>

        <!-- Contrôle d'accès à la saisie de copropriétaire -->
        <?php if (! $isReadOnly) { ?>
            <button
                onclick="document.getElementById('modal-add-cop')?.classList.remove('hidden')"
                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
            >
                <span>➕ Nouveau Copropriétaire</span>
            </button>
        <?php } else { ?>
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                <span>🔒</span>
                <span>Ajout désactivé (Lecture seule)</span>
            </span>
        <?php } ?>
    </div>

    <!-- Tableau du registre des copropriétaires -->
    <div class="rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Copropriétaire</th>
                        <th class="p-3.5">CIN</th>
                        <th class="p-3.5">Contact (Tél / Email)</th>
                        <th class="p-3.5">Lots & Quotes-parts</th>
                        <th class="p-3.5">Qualité</th>
                        <th class="p-3.5 text-right">Identifiant Accès</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <?php if (empty($coproprietaires)) { ?>
                        <!-- État affiché lorsqu'aucun copropriétaire n'est présent -->
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400">Aucun copropriétaire enregistré dans cette base.</td>
                        </tr>
                    <?php } else { ?>
                        <!-- Boucle d'affichage des copropriétaires -->
                        <?php foreach ($coproprietaires as $c) { ?>
                            <?php
                                // Filtrage des lots appartenant à ce copropriétaire pour sommer ses tantièmes
                                $cLots = array_filter($lots, fn ($l) => ($l['coproprietaire_id'] ?? null) === $c['id']);
                            $sumTantiemes = array_sum(array_column($cLots, 'tantiemes'));
                            ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($c['civilite'].' '.$c['prenom'].' '.$c['nom']) ?>
                                </td>
                                <td class="p-3.5 font-mono text-slate-600 dark:text-slate-400"><?= htmlspecialchars($c['cin'] ?: 'N/A') ?></td>
                                <td class="p-3.5 text-slate-600 dark:text-slate-400">
                                    <div><?= htmlspecialchars($c['telephone'] ?: 'N/A') ?></div>
                                    <div class="text-[11px] text-blue-600 dark:text-blue-400"><?= htmlspecialchars($c['email'] ?: 'N/A') ?></div>
                                </td>
                                <td class="p-3.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200"><?= count($cLots) ?> lot(s)</span>
                                    <span class="text-[10px] text-slate-500 font-mono">(<?= $sumTantiemes ?>/10 000)</span>
                                </td>
                                <td class="p-3.5">
                                    <!-- Badge indiquant si le copropriétaire réside sur place ou est bailleur -->
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $c['est_resident'] ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' ?>">
                                        <?= $c['est_resident'] ? 'Résident' : 'Bailleur' ?>
                                    </span>
                                </td>
                                <td class="p-3.5 text-right font-mono text-[11px]">
                                    <!-- Identifiant officiel permettant la connexion au portail résident -->
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 font-bold">
                                        <?= htmlspecialchars($c['friendly_username'] ?? ($c['email'] ?: 'user@'.TenantDB::getResidenceTag())) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE D'AJOUT D'UN NOUVEAU COPROPRIÉTAIRE -->
<?php if (! $isReadOnly) { ?>
<div id="modal-add-cop" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
    <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="font-bold text-sm">Ajouter un Copropriétaire</h3>
                <p class="text-[11px] text-slate-500">Compte portail généré : <strong class="font-mono text-emerald-600">user@<?= TenantDB::getResidenceTag() ?></strong></p>
            </div>
            <button onclick="document.getElementById('modal-add-cop').classList.add('hidden')" class="text-slate-400">&times;</button>
        </div>
        <form action="actions/add_coproprietaire.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Civilité</label>
                    <select name="civilite" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                        <option value="M.">M.</option>
                        <option value="Mme">Mme</option>
                        <option value="Mlle">Mlle</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold mb-1">Nom de Famille *</label>
                    <input type="text" name="nom" required placeholder="EL OMARI" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl uppercase font-bold">
                </div>
            </div>
            <div>
                <label class="block font-semibold mb-1">Prénom *</label>
                <input type="text" name="prenom" required placeholder="Hicham" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Numéro CIN</label>
                    <input type="text" name="cin" placeholder="BK123456" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl uppercase font-mono">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Téléphone</label>
                    <input type="text" name="telephone" placeholder="+212 6..." class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>
            </div>
            <div>
                <label class="block font-semibold mb-1">Email Personnel (Notifications)</label>
                <input type="email" name="email" placeholder="hicham.elomari@gmail.com" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
            </div>

            <!-- Rappel pédagogique du format de compte généré -->
            <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 text-[11px] text-emerald-800 dark:text-emerald-300">
                <span>🔑 <strong>Identifiant de connexion créé :</strong> <code class="font-bold font-mono">prenom.nom@<?= TenantDB::getResidenceTag() ?></code> (mot de passe initial : <code class="font-mono">resident2026</code>)</span>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="est_resident" id="est_resident" checked class="rounded border-slate-300">
                <label for="est_resident" class="font-medium cursor-pointer">Occupe son lot (Résident dans l'immeuble)</label>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-add-cop').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-zinc-900 rounded-xl">Annuler</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-bold rounded-xl shadow">Enregistrer Copropriétaire</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

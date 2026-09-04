<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/delegues.php
 * TYPE           : Vue Métier / Bureau Syndical & Matrice de Délégation RBAC
 * MODULE         : Gouvernance Partagée, Droits Granulaires & Contrôle d'Accès
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Désignation de membres du bureau (Vice-Syndic, Trésorier, Secrétaire)
 *                  - Délégation de compétences et traçabilité des opérations
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module permet au Syndic Principal en exercice de désigner des délégués
 * choisis exclusivement parmi les copropriétaires inscrits dans l'immeuble.
 *
 * Principes et fonctionnalités :
 * 1. Éligibilité Restreinte :
 *    - Seuls les résidents répertoriés dans la table `coproprietaires` peuvent être
 *      investis d'un mandat de délégué syndical.
 * 2. Rôles et Titres Métiers :
 *    - Vice-Syndic Adjoint : suppléance de gestion et supervision technique/administrative.
 *    - Comptable / Trésorier : gestion de trésorerie, encaissements, factures et relances.
 *    - Secrétaire Général : organisation des AG, rédaction des PV et registres d'archives.
 *    - Membre Délégué du Bureau : compétences spécifiques ad hoc.
 * 3. Matrice de Permissions Granulaires (14 Modules) :
 *    - Chaque délégué ne peut accéder qu'aux modules autorisés dans son profil.
 *    - Des packs prédéfinis (Presets) permettent une configuration rapide en 1 clic.
 * 4. Cycle de Vie du Mandat :
 *    - Suspension temporaire (toggle status) ou révocation définitive (suppression).
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS ET CONTRÔLE DE SESSION GESTIONNAIRE
// ----------------------------------------------------------------------------
// tenant_auth.php : Valide l'authentification et extrait l'utilisateur actif ($user).
require_once dirname(__DIR__).'/includes/tenant_auth.php';

// tenant_db.php : Couche de données SQLite de la copropriété.
require_once dirname(__DIR__).'/includes/tenant_db.php';

// brand.php : Gestion graphique du logo.
require_once dirname(__DIR__).'/includes/brand.php';

// Vérification de la session active du gestionnaire.
$user = requireAuth();

// Résolution du GUID du tenant actif.
$guid = TenantDB::resolveGuid();

// Contrôle de la licence commerciale du tenant (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();

// ----------------------------------------------------------------------------
// CHARGEMENT DES DÉLÉGUÉS ET DES RÉSIDENTS ÉLIGIBLES
// ----------------------------------------------------------------------------
// Liste des délégués actuellement enregistrés dans la table delegues_syndic.
$delegates = TenantDB::getDelegates();

// Liste des copropriétaires pouvant être nommés délégués (avec compte utilisateur actif).
$eligibleResidents = TenantDB::getEligibleResidentsForDelegation();

// Messages flash transmis via l'URL.
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// ----------------------------------------------------------------------------
// DÉNOMBREMENT ET RÉPARTITION DES RÔLES AU BUREAU
// ----------------------------------------------------------------------------
$countViceSyndic = 0;
$countComptable = 0;
$countSecretaire = 0;

foreach ($delegates as $d) {
    if ($d['statut'] === 'actif') {
        if ($d['titre_role'] === 'vice_syndic') {
            $countViceSyndic++;
        } elseif ($d['titre_role'] === 'comptable') {
            $countComptable++;
        } elseif ($d['titre_role'] === 'secretaire') {
            $countSecretaire++;
        }
    }
}

// Définition exhaustive des 14 modules du système avec icônes et catégories.
$moduleLabels = [
    'dashboard' => ['label' => 'Cockpit Financier', 'icon' => '📊', 'cat' => 'Finance'],
    'annexes' => ['label' => 'Annexes Légales (1 à 5)', 'icon' => '📜', 'cat' => 'Légal'],
    'coproprietaires' => ['label' => 'Copropriétaires', 'icon' => '👥', 'cat' => 'Résidents'],
    'lots' => ['label' => 'Lots & Tantièmes', 'icon' => '🏠', 'cat' => 'Résidents'],
    'appels' => ['label' => 'Appels de Fonds', 'icon' => '💳', 'cat' => 'Finance'],
    'paiements' => ['label' => 'Encaissements & Quittances', 'icon' => '🧾', 'cat' => 'Finance'],
    'relances' => ['label' => 'Impayés & Contentieux', 'icon' => '⚠️', 'cat' => 'Finance'],
    'depenses' => ['label' => 'Dépenses & Factures', 'icon' => '💸', 'cat' => 'Finance'],
    'fournisseurs' => ['label' => 'Prestataires & Contrats', 'icon' => '🤝', 'cat' => 'Fournisseurs'],
    'carnet' => ['label' => "Carnet d'Entretien", 'icon' => '🛠️', 'cat' => 'Technique'],
    'assemblees' => ['label' => 'Assemblées Générales & PV', 'icon' => '🗳️', 'cat' => 'Gouvernance'],
    'reclamations' => ['label' => 'Tickets & Réclamations', 'icon' => '🔧', 'cat' => 'Technique'],
    'projets' => ['label' => 'Projets & Chantiers', 'icon' => '🏗️', 'cat' => 'Technique'],
    'settings' => ['label' => 'Paramètres & Logo', 'icon' => '⚙️', 'cat' => 'Admin'],
];
?>

<div class="space-y-6">
    <!-- En-tête de page avec bouton de nomination d'un délégué -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Bureau Syndical & Délégués</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/20 uppercase">
                    Gouvernance Partagée
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Délégation officielle de pouvoirs aux copropriétaires (Vice-Syndic, Comptable, Secrétaire Général) avec droits d'accès granulaires (Loi 18-00)
            </p>
        </div>

        <div>
            <!-- Bouton déclenchant la modale de désignation de délégué -->
            <?php if (! $isReadOnly) { ?>
                <button
                    onclick="openAddDelegateModal()"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white text-xs font-bold transition flex items-center gap-2 shadow-md"
                >
                    <span>➕ Désigner un Délégué</span>
                </button>
            <?php } else { ?>
                <span class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                    <span>🔒</span>
                    <span>Modifications verrouillées (Lecture seule)</span>
                </span>
            <?php } ?>
        </div>
    </div>

    <!-- Alertes et Messages Flash -->
    <?php if ($msg === 'delegate_added') { ?>
        <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2">
            <span>✅</span>
            <span>Nouveau délégué désigné avec succès. Ses droits d'accès administratifs sont immédiatement opérationnels.</span>
        </div>
    <?php } elseif ($msg === 'delegate_updated') { ?>
        <div class="p-3.5 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 text-xs flex items-center gap-2">
            <span>✅</span>
            <span>Permissions et mandat du délégué mis à jour avec succès.</span>
        </div>
    <?php } elseif ($msg === 'delegate_deleted') { ?>
        <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-300 text-xs flex items-center gap-2">
            <span>ℹ️</span>
            <span>Délégation révoquée. L'utilisateur retrouve ses droits exclusifs de simple résident.</span>
        </div>
    <?php } elseif ($msg === 'status_updated') { ?>
        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 text-xs flex items-center gap-2">
            <span>✅</span>
            <span>Statut de la délégation mis à jour.</span>
        </div>
    <?php } elseif ($error === 'unauthorized_module') { ?>
        <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
            <span>⚠️</span>
            <span>Accès Restreint : Votre profil de délégué ne dispose pas des autorisations requises pour ce module.</span>
        </div>
    <?php } elseif ($error) { ?>
        <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php } ?>

    <!-- 3 CARTES DE SYNTHÈSE DU BUREAU SYNDICAL -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Carte 1 : Nombre total de membres délégués -->
        <div class="p-4 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] flex items-center justify-center font-bold text-xl shrink-0">
                👑
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Membres Délégués</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white"><?= count($delegates) ?></div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Élus parmi les copropriétaires</div>
            </div>
        </div>

        <!-- Carte 2 : Rôle Comptable & Finances -->
        <div class="p-4 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl shrink-0">
                💼
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Comptable & Finances</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white"><?= $countComptable ?></div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Trésorerie & Encaissements</div>
            </div>
        </div>

        <!-- Carte 3 : Vice-Syndic & Secrétariat Général -->
        <div class="p-4 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xl shrink-0">
                📜
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Vice-Syndic & Secrétaire</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white"><?= $countViceSyndic + $countSecretaire ?></div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Suppléance & Assemblées Générales</div>
            </div>
        </div>
    </div>

    <!-- TABLEAU DU REGISTRE DES DÉLÉGUÉS DU SYNDIC -->
    <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-xl overflow-hidden">
        <div class="p-4 border-b border-[#F0E4DC] dark:border-[#3D154F] flex items-center justify-between">
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">Membres Actifs du Bureau Syndical</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Copropriétaires investis d'un mandat avec droits d'accès spécifiques</p>
            </div>
            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-700 dark:text-slate-300">
                <?= count($delegates) ?> / <?= count($eligibleResidents) ?> Copropriétaires
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-[#14021C]/80 border-b border-[#F0E4DC] dark:border-[#3D154F] text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                    <tr>
                        <th class="p-3.5">Copropriétaire Délégué</th>
                        <th class="p-3.5">Lot & Tantièmes</th>
                        <th class="p-3.5">Rôle & Titre</th>
                        <th class="p-3.5">Permissions Accordées</th>
                        <th class="p-3.5">Statut</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#3D154F]/50">
                    <?php if (empty($delegates)) { ?>
                        <!-- État vide lorsqu'aucun délégué n'a été nommé -->
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                Aucun délégué désigné pour le moment. Cliquez sur « ➕ Désigner un Délégué » pour nommer un Vice-Syndic, Comptable ou Secrétaire parmi vos résidents.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <!-- Parcours et affichage de chaque délégué -->
                        <?php foreach ($delegates as $d) { ?>
                            <?php
                            // Attribution des styles de badges visuels selon la fonction attribuée
                            $roleColors = [
                                'vice_syndic' => 'bg-purple-500/15 text-purple-600 dark:text-purple-300 border-purple-500/30',
                                'comptable' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border-emerald-500/30',
                                'secretaire' => 'bg-blue-500/15 text-blue-600 dark:text-blue-300 border-blue-500/30',
                                'delegue' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border-amber-500/30',
                            ];
                            $roleBadgeColor = $roleColors[$d['titre_role']] ?? 'bg-slate-500/15 text-slate-600 border-slate-500/30';
                            $lotStr = ! empty($d['lots']) ? implode(', ', array_map(fn ($l) => '#'.$l['numero'], $d['lots'])) : 'N/A';
                            $totalTant = array_sum(array_column($d['lots'], 'tantiemes'));
                            ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-[#250832]/50 transition">
                                <!-- Colonne Identité & Coordonnées -->
                                <td class="p-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                                            <?= strtoupper(substr($d['cop_nom'] ?? 'D', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                <?= htmlspecialchars(($d['cop_prenom'] ?? '').' '.$d['cop_nom']) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">
                                                <?= htmlspecialchars($d['user_email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Colonne Propriété Foncière (Lots et tantièmes) -->
                                <td class="p-3.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">Lot <?= htmlspecialchars($lotStr) ?></span>
                                    <div class="text-[10px] text-slate-400 font-mono"><?= $totalTant ?> / 10 000 tantièmes</div>
                                </td>

                                <!-- Colonne Titre Officiel au Bureau -->
                                <td class="p-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold border <?= $roleBadgeColor ?>">
                                        <span><?= $d['titre_role'] === 'vice_syndic' ? '👑' : ($d['titre_role'] === 'comptable' ? '💼' : ($d['titre_role'] === 'secretaire' ? '📜' : '🎖️')) ?></span>
                                        <span><?= htmlspecialchars($d['role_label']) ?></span>
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-1">Nommé le <?= htmlspecialchars($d['date_nomination']) ?></div>
                                </td>

                                <!-- Colonne Badges des Permissions Accordées -->
                                <td class="p-3.5 max-w-xs">
                                    <div class="flex flex-wrap gap-1">
                                        <?php if (count($d['permissions_array']) >= count($moduleLabels)) { ?>
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/30">
                                                🔓 Accès Intégral (<?= count($d['permissions_array']) ?> modules)
                                            </span>
                                        <?php } else { ?>
                                            <?php foreach (array_slice($d['permissions_array'], 0, 4) as $pKey) { ?>
                                                <?php $meta = $moduleLabels[$pKey] ?? null; ?>
                                                <?php if ($meta) { ?>
                                                    <span class="px-1.5 py-0.5 rounded-lg text-[10px] bg-slate-100 dark:bg-[#250832] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#3D154F]">
                                                        <?= $meta['icon'] ?> <?= $meta['label'] ?>
                                                    </span>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if (count($d['permissions_array']) > 4) { ?>
                                                <span class="px-1.5 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-[#250832] text-slate-500">
                                                    +<?= count($d['permissions_array']) - 4 ?> autres
                                                </span>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                </td>

                                <!-- Colonne Statut d'Activité -->
                                <td class="p-3.5">
                                    <?php if ($d['statut'] === 'actif') { ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Actif</span>
                                        </span>
                                    <?php } else { ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                            <span>⏸️ Suspendu</span>
                                        </span>
                                    <?php } ?>
                                </td>

                                <!-- Colonne Actions de Gestion -->
                                <td class="p-3.5 text-right">
                                    <?php if (! $isReadOnly) { ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Modification des permissions -->
                                            <button
                                                type="button"
                                                onclick='openEditDelegateModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#250832] dark:hover:bg-[#340C44] text-slate-700 dark:text-slate-300 font-bold transition text-[11px]"
                                                title="Modifier les permissions"
                                            >
                                                ✏️ Modifier
                                            </button>

                                            <!-- Suspension ou Réactivation du mandat -->
                                            <form action="actions/manage_delegate.php" method="POST" class="inline">
                                                <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                                                <button
                                                    type="submit"
                                                    class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-[#250832] text-slate-500 text-xs transition"
                                                    title="<?= $d['statut'] === 'actif' ? 'Suspendre' : 'Activer' ?>"
                                                >
                                                    <?= $d['statut'] === 'actif' ? '⏸️' : '▶️' ?>
                                                </button>
                                            </form>

                                            <!-- Révocation de la délégation -->
                                            <form action="actions/manage_delegate.php" method="POST" class="inline" onsubmit="return confirm('Révoquer la délégation de ce copropriétaire ?')">
                                                <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                                                <button
                                                    type="submit"
                                                    class="p-1.5 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-500 text-xs transition"
                                                    title="Révoquer la délégation"
                                                >
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    <?php } else { ?>
                                        <span class="text-[10px] text-slate-400">Verrouillé</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE D'AJOUT OU DE MODIFICATION D'UN DÉLÉGUÉ DU SYNDIC -->
<?php if (! $isReadOnly) { ?>
<div id="modal-delegate" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
    <div class="w-full max-w-2xl rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-2xl p-6 space-y-5 text-slate-900 dark:text-zinc-100 max-h-[92vh] overflow-y-auto">
        <!-- Header de la Modale -->
        <div class="flex items-center justify-between pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
            <div>
                <h3 id="modal-title" class="font-bold text-base text-slate-900 dark:text-white">Désigner un Délégué du Syndic</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Sélectionnez un résident et configurez ses droits d'accès à l'interface d'administration</p>
            </div>
            <button onclick="closeDelegateModal()" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
        </div>

        <form action="actions/manage_delegate.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id" id="form-id" value="">

            <!-- 1. Sélection du Copropriétaire Résident Éligible -->
            <div id="wrapper-coproprietaire">
                <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">
                    Sélectionner le Copropriétaire Résident *
                </label>
                <select
                    name="coproprietaire_id"
                    id="select-coproprietaire"
                    required
                    class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-semibold text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                >
                    <option value="">-- Choisir parmi les résidents de la copropriété --</option>
                    <?php foreach ($eligibleResidents as $r) { ?>
                        <option value="<?= htmlspecialchars($r['id']) ?>">
                            <?= htmlspecialchars(($r['prenom'] ?? '').' '.$r['nom']) ?> &bull; Lot #<?= htmlspecialchars($r['lot_numero'] ?? 'Non affecté') ?> (<?= htmlspecialchars($r['user_email'] ?? $r['email']) ?>)
                        </option>
                    <?php } ?>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Seuls les copropriétaires inscrits dans le registre peuvent être désignés membres du bureau.</p>
            </div>

            <!-- 2. Rôle, Fonction & Libellé Personnalisé -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Fonction / Rôle *</label>
                    <select
                        name="titre_role"
                        id="select-titre-role"
                        onchange="onRoleChange(this.value)"
                        class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-bold text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                    >
                        <option value="vice_syndic">👑 Vice-Syndic Adjoint</option>
                        <option value="comptable">💼 Comptable / Trésorier</option>
                        <option value="secretaire">📜 Secrétaire Général</option>
                        <option value="delegue">🎖️ Membre Délégué du Bureau</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Libellé Affiché sur l'Interface *</label>
                    <input
                        type="text"
                        name="role_label"
                        id="input-role-label"
                        value="Vice-Syndic Adjoint"
                        required
                        class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-bold text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                    />
                </div>
            </div>

            <!-- 3. Raccourcis de Modèles de Permissions (Presets RBAC) -->
            <div class="p-3 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-[11px] text-slate-700 dark:text-slate-300">⚡ Modèles de Permissions Prédéfinis :</span>
                    <button type="button" onclick="selectAllPermissions(true)" class="text-[10px] text-[#D91C6E] dark:text-[#F26968] font-bold hover:underline">Tout Cocher</button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        type="button"
                        onclick="applyPreset('vice_syndic')"
                        class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] hover:border-[#D91C6E] text-[10.5px] font-semibold transition flex items-center gap-1 shadow-sm"
                    >
                        <span>👑 Pack Vice-Syndic</span>
                    </button>
                    <button
                        type="button"
                        onclick="applyPreset('comptable')"
                        class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] hover:border-[#D91C6E] text-[10.5px] font-semibold transition flex items-center gap-1 shadow-sm"
                    >
                        <span>💼 Pack Comptable</span>
                    </button>
                    <button
                        type="button"
                        onclick="applyPreset('secretaire')"
                        class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] hover:border-[#D91C6E] text-[10.5px] font-semibold transition flex items-center gap-1 shadow-sm"
                    >
                        <span>📜 Pack Secrétaire</span>
                    </button>
                    <button
                        type="button"
                        onclick="selectAllPermissions(false)"
                        class="px-2.5 py-1 rounded-xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] hover:border-slate-400 text-[10.5px] text-slate-500 transition"
                    >
                        <span>Décocher Tout</span>
                    </button>
                </div>
            </div>

            <!-- 4. Grille Granulaire des Permissions Modules (14 cases) -->
            <div>
                <label class="block font-semibold mb-2 text-slate-700 dark:text-slate-300">
                    Modules Accessibles dans l'Espace Syndic Admin *
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto p-1">
                    <?php foreach ($moduleLabels as $key => $m) { ?>
                        <label class="flex items-center gap-2.5 p-2 rounded-xl bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] hover:border-[#D91C6E] cursor-pointer transition select-none">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="<?= $key ?>"
                                class="perm-checkbox rounded text-[#D91C6E] focus:ring-[#D91C6E]"
                            >
                            <span class="text-base"><?= $m['icon'] ?></span>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 dark:text-white leading-tight"><?= $m['label'] ?></div>
                                <div class="text-[9.5px] text-slate-400"><?= $m['cat'] ?></div>
                            </div>
                        </label>
                    <?php } ?>
                </div>
            </div>

            <!-- 5. Notes & Délibération AG -->
            <div>
                <label class="block font-semibold mb-1 text-slate-700 dark:text-slate-300">Notes & Délibération AG (optionnel)</label>
                <textarea
                    name="notes"
                    id="input-notes"
                    rows="2"
                    placeholder="Ex: Élu par décision de l'Assemblée Générale Ordinaire du 15 Janvier 2026."
                    class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                ></textarea>
            </div>

            <!-- Boutons de Soumission -->
            <div class="pt-3 border-t border-[#F0E4DC] dark:border-[#3D154F] flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    onclick="closeDelegateModal()"
                    class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-700 dark:text-slate-300 font-semibold"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold transition shadow-md"
                >
                    <span id="btn-submit-text">Enregistrer la Délégation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPTS JAVASCRIPT : GESTION DES PRESETS DE PERMISSIONS ET DES MODALES -->
<script>
    // Définition des profils de permissions pré-configurés
    const presets = {
        vice_syndic: [
            'dashboard', 'annexes', 'coproprietaires', 'lots', 'appels', 'paiements',
            'relances', 'depenses', 'fournisseurs', 'carnet', 'assemblees', 'reclamations', 'projets'
        ],
        comptable: [
            'dashboard', 'annexes', 'appels', 'paiements', 'relances', 'depenses', 'fournisseurs'
        ],
        secretaire: [
            'assemblees', 'coproprietaires', 'lots', 'carnet', 'reclamations', 'projets'
        ]
    };

    // Sélectionne ou désélectionne l'ensemble des modules d'administration
    function selectAllPermissions(val) {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = val);
    }

    // Applique un jeu de permissions spécifique au profil sélectionné
    function applyPreset(key) {
        selectAllPermissions(false);
        const list = presets[key] || [];
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            if (list.includes(cb.value)) {
                cb.checked = true;
            }
        });
    }

    // Adapte le libellé par défaut et les permissions lors du changement de rôle
    function onRoleChange(role) {
        const labels = {
            vice_syndic: 'Vice-Syndic Adjoint',
            comptable: 'Comptable / Trésorier',
            secretaire: 'Secrétaire Général',
            delegue: 'Membre Délégué du Bureau'
        };
        document.getElementById('input-role-label').value = labels[role] || 'Délégué';
        applyPreset(role);
    }

    // Ouvre la modale pour une nouvelle nomination
    function openAddDelegateModal() {
        document.getElementById('modal-title').textContent = "Désigner un Délégué du Syndic";
        document.getElementById('form-action').value = "add";
        document.getElementById('form-id').value = "";
        document.getElementById('wrapper-coproprietaire').style.display = 'block';
        document.getElementById('select-coproprietaire').required = true;
        document.getElementById('select-coproprietaire').value = "";
        document.getElementById('select-titre-role').value = "vice_syndic";
        document.getElementById('input-role-label').value = "Vice-Syndic Adjoint";
        document.getElementById('input-notes').value = "";
        document.getElementById('btn-submit-text').textContent = "Enregistrer la Délégation";
        applyPreset('vice_syndic');
        document.getElementById('modal-delegate').classList.remove('hidden');
    }

    // Ouvre la modale pour modifier un délégué existant
    function openEditDelegateModal(del) {
        document.getElementById('modal-title').textContent = "Modifier les Permissions du Délégué";
        document.getElementById('form-action').value = "update";
        document.getElementById('form-id').value = del.id;
        document.getElementById('wrapper-coproprietaire').style.display = 'none';
        document.getElementById('select-coproprietaire').required = false;
        document.getElementById('select-titre-role').value = del.titre_role;
        document.getElementById('input-role-label').value = del.role_label;
        document.getElementById('input-notes').value = del.notes || "";
        document.getElementById('btn-submit-text').textContent = "Mettre à Jour les Permissions";

        selectAllPermissions(false);
        const perms = del.permissions_array || [];
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            if (perms.includes(cb.value)) {
                cb.checked = true;
            }
        });

        document.getElementById('modal-delegate').classList.remove('hidden');
    }

    // Ferme la modale
    function closeDelegateModal() {
        document.getElementById('modal-delegate').classList.add('hidden');
    }
</script>
<?php } ?>

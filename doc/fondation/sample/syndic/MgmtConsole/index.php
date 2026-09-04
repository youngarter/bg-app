<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtConsole/index.php
 * TYPE           : Tableau de Bord Principal / Console d'Administration Centrale
 * MODULE         : Super-Admin, Provisionnement Multi-Tenant & Contrôle de Licences
 * CADRE JURIDIQUE: Architecture SaaS Multi-Copropriétés & Supervision Technique
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce tableau de bord constitue le centre de contrôle global pour le Super-Admin
 * de la plateforme Bayan Gestion.
 *
 * Fonctionnalités majeures :
 * 1. Métriques Clés Consolidées (KPIs) :
 *    - Nombre total de résidences gérées (actives, en période de grâce, lecture seule).
 *    - Parc total de lots de copropriété enregistrés sur l'ensemble des bases.
 *    - Volume d'espace disque consommé par les fichiers SQLite partitionnés (KB).
 *    - État de santé et respect du partitionnement physique (Air-Gap).
 * 2. Moteur de Recherche & Filtrage Dynamique :
 *    - Recherche plein-texte multi-critères : nom de résidence, ville, syndic,
 *      code unique de copropriété ou GUID tenant.
 *    - Filtres d'état de licence : Toutes, Actives, En Grâce (30 jours), Lecture Seule.
 * 3. Gestion du Cycle de Vie des Tenants (Copropriétés) :
 *    - Provisionnement en un clic d'une nouvelle copropriété avec base SQLite
 *      vierge et génération conforme RFC 4122 d'un GUID v4 aléatoire.
 *    - Renouvellement de licences (+6 mois, +12 mois / 1 an).
 *    - Verrouillage administratif ou d'impayé en mode lecture seule (read-only).
 *    - Téléchargement direct d'une base SQLite dédiée.
 *    - Suppression définitive sécurisée avec confirmation textuelle 'DELETE'.
 * 4. Sauvegardes & Traçabilité :
 *    - Exportation de l'archive complète ZIP englobant master.sqlite et tous les tenants.
 *    - Visualisation des derniers événements du journal d'audit de la console.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS DES DÉPENDANCES ET CONTRÔLE D'ACCÈS SUPER-ADMIN
// ----------------------------------------------------------------------------
// auth.php : Initialise la session Super-Admin ('syndic_master_session') et vérifie le rôle.
require_once __DIR__.'/includes/auth.php';

// master_db.php : Couche d'accès à la base centrale master.sqlite.
require_once __DIR__.'/includes/master_db.php';

// Vérification stricte : redirection vers login.php si non connecté en Super-Admin.
$currentAdmin = requireSuperAdmin();

// ----------------------------------------------------------------------------
// CHARGEMENT DES STATISTIQUES GLOBALES ET DE LA LISTE DES TENANTS
// ----------------------------------------------------------------------------
// Récupération des métriques agrégées (nb tenants, lots, taille disque, logs récents).
$stats = MasterDB::getMasterStats();

// Récupération de l'ensemble des copropriétés enregistrées avec calcul des états de licence.
$tenants = MasterDB::getAllTenants();

// ----------------------------------------------------------------------------
// GESTION DE LA RECHERCHE ET DU FILTRAGE
// ----------------------------------------------------------------------------
// Nettoyage de la chaîne de recherche saisie dans l'URL.
$search = trim($_GET['search'] ?? '');

// Filtre de statut de licence sélectionné ('all', 'active', 'grace', 'readonly').
$filter = $_GET['filter'] ?? 'all';

// Application du filtre en mémoire sur le tableau des tenants.
$filteredTenants = array_filter($tenants, function ($t) use ($search, $filter) {
    // 1. Recherche plein-texte multi-champs
    if ($search !== '') {
        $q = strtolower($search);
        $match = str_contains(strtolower($t['nom']), $q)
            || str_contains(strtolower($t['ville']), $q)
            || str_contains(strtolower($t['nom_syndic']), $q)
            || str_contains(strtolower($t['code_unique']), $q)
            || str_contains(strtolower($t['id']), $q);
        if (! $match) {
            return false;
        }
    }

    // 2. Filtrage par état de validité de la licence
    if ($filter === 'active') {
        return $t['licenseStatus'] === 'active';
    }
    if ($filter === 'grace') {
        return $t['licenseStatus'] === 'grace_period';
    }
    if ($filter === 'readonly') {
        return $t['isReadOnly'];
    }

    return true;
});

// ----------------------------------------------------------------------------
// GESTION DES MESSAGES FLASHS ET NOTIFICATIONS DE PROVISIONNEMENT
// ----------------------------------------------------------------------------
// Détection des identifiants générés après un provisionnement récent (stockés en session).
$newTenant = $_SESSION['new_tenant_created'] ?? null;
unset($_SESSION['new_tenant_created']);

// Messages flash transmis via l'URL (confirmation d'action ou erreur).
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

// Inclusion du layout d'en-tête de la console centrale.
require_once __DIR__.'/includes/header.php';
?>

<main class="flex-1 max-w-7xl w-full mx-auto p-6 space-y-6">

    <!-- ALERTES ET NOTIFICATIONS FLASH -->
    <?php if ($msg) { ?>
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <a href="index.php" class="text-emerald-600 hover:underline">Fermer</a>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-red-950/40 border border-rose-200 dark:border-red-800/60 text-rose-800 dark:text-red-300 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <a href="index.php" class="text-rose-600 hover:underline">Fermer</a>
        </div>
    <?php } ?>

    <!-- BANNIÈRE DE NOUVELLE COPROPRIÉTÉ CRÉÉE (AFFICHAGE IMMÉDIAT DU GUID ET DE L'URL) -->
    <?php if ($newTenant) { ?>
        <div class="p-6 rounded-3xl bg-emerald-50 dark:bg-emerald-950/50 border-2 border-emerald-500 shadow-xl text-emerald-900 dark:text-emerald-200 space-y-4 animate-fade-in">
            <div class="flex items-center justify-between pb-2 border-b border-emerald-200 dark:border-emerald-800/60">
                <div class="flex items-center gap-2 text-base font-bold text-emerald-700 dark:text-emerald-400">
                    <span>🎉</span>
                    <span>Nouvelle Copropriété & Base SQLite Vierge Créées avec Succès !</span>
                </div>
                <button onclick="this.closest('.p-6').remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                <div class="p-3 bg-white dark:bg-zinc-900 rounded-xl border border-emerald-200 dark:border-zinc-800 space-y-1">
                    <div><strong class="text-slate-500">Nom :</strong> <?= htmlspecialchars($newTenant['nom']) ?></div>
                    <div><strong class="text-slate-500">Tenant GUID :</strong> <span class="text-blue-600 dark:text-blue-400 font-bold"><?= htmlspecialchars($newTenant['id']) ?></span></div>
                    <div><strong class="text-slate-500">Syndic Admin :</strong> <?= htmlspecialchars($newTenant['email']) ?></div>
                    <div><strong class="text-slate-500">Mot de Passe :</strong> <span class="text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($newTenant['password']) ?></span></div>
                </div>

                <div class="p-3 bg-white dark:bg-zinc-900 rounded-xl border border-emerald-200 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700 dark:text-zinc-300">URL Officielle de Connexion Syndic :</span>
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('<?= htmlspecialchars($newTenant['syndicLoginUrl']) ?>'); alert('Lien copié dans le presse-papier !');"
                            class="px-2 py-0.5 rounded-lg bg-blue-600 text-white font-bold text-[10px]"
                        >
                            Copier
                        </button>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-100 dark:bg-black/60 text-blue-600 dark:text-blue-400 break-all select-all font-bold">
                        <?= htmlspecialchars($newTenant['syndicLoginUrl']) ?>
                    </div>
                    <a
                        href="<?= htmlspecialchars($newTenant['syndicLoginUrl']) ?>"
                        target="_blank"
                        class="inline-block text-[11px] text-blue-600 hover:underline font-bold"
                    >
                        Ouvrir le portail dans un nouvel onglet ↗
                    </a>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- 4 CARTES KPI MASTER (INDICATEURS CLÉS DU PARC) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <!-- KPI 1 : Résidences déployées -->
        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800/80 shadow-sm space-y-1 transition-colors">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-semibold">
                <span>Résidences Gérées</span>
                <span>🏢</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= $stats['totalTenants'] ?></div>
            <p class="text-[11px] text-slate-500 dark:text-zinc-400"><?= $stats['activeTenants'] ?> actives &bull; <?= $stats['gracePeriodTenants'] ?> en grâce</p>
        </div>

        <!-- KPI 2 : Total des lots sous gestion -->
        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800/80 shadow-sm space-y-1 transition-colors">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-semibold">
                <span>Parc de Lots</span>
                <span>📑</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= $stats['totalLots'] ?></div>
            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Lots répartis dans les bases</p>
        </div>

        <!-- KPI 3 : Stockage SQLite total consommé -->
        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800/80 shadow-sm space-y-1 transition-colors">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-semibold">
                <span>Stockage SQLite</span>
                <span>💾</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white font-mono"><?= $stats['totalStorageKB'] ?> KB</div>
            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Bases isolées par tenant</p>
        </div>

        <!-- KPI 4 : Garantie d'étanchéité ACID -->
        <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900/60 border border-slate-200 dark:border-zinc-800/80 shadow-sm space-y-1 transition-colors">
            <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400 text-xs font-semibold">
                <span>Sécurité & Isolement</span>
                <span>🛡️</span>
            </div>
            <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">ACID SQLite</div>
            <p class="text-[11px] text-slate-500 dark:text-zinc-400">Partitionnement physique 100%</p>
        </div>
    </div>

    <!-- BARRE DE RECHERCHE ET ONGLETS DE FILTRAGE DES LICENCES -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
        <form method="GET" class="relative w-full sm:w-80">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input
                type="text"
                name="search"
                placeholder="Rechercher résidence, ville, syndic, GUID..."
                value="<?= htmlspecialchars($search) ?>"
                class="w-full pl-9 pr-3.5 py-2 bg-white dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:border-blue-500 focus:outline-none"
            />
            <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
        </form>

        <!-- Onglets de filtres -->
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-zinc-900/80 p-1 border border-slate-200 dark:border-zinc-800 rounded-xl w-full sm:w-auto text-xs overflow-x-auto">
            <a href="?filter=all&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg font-medium transition whitespace-nowrap <?= $filter === 'all' ? 'bg-white dark:bg-zinc-800 text-slate-900 dark:text-white font-bold shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' ?>">
                Toutes (<?= count($tenants) ?>)
            </a>
            <a href="?filter=active&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg font-medium transition whitespace-nowrap <?= $filter === 'active' ? 'bg-white dark:bg-zinc-800 text-emerald-600 dark:text-emerald-400 font-bold shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' ?>">
                Licences Actives (<?= $stats['activeTenants'] ?>)
            </a>
            <a href="?filter=grace&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg font-medium transition whitespace-nowrap <?= $filter === 'grace' ? 'bg-white dark:bg-zinc-800 text-amber-600 dark:text-amber-400 font-bold shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' ?>">
                En Grâce 30j (<?= $stats['gracePeriodTenants'] ?>)
            </a>
            <a href="?filter=readonly&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded-lg font-medium transition whitespace-nowrap <?= $filter === 'readonly' ? 'bg-white dark:bg-zinc-800 text-rose-600 dark:text-red-400 font-bold shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white' ?>">
                Lecture Seule (<?= $stats['readOnlyTenants'] ?>)
            </a>
        </div>
    </div>

    <!-- GRILLE DES CARTES DE COPROPRIÉTÉS (TENANTS) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($filteredTenants)) { ?>
            <div class="col-span-3 py-12 text-center text-slate-500 dark:text-zinc-500 text-xs">
                Aucune copropriété ne correspond à votre recherche.
            </div>
        <?php } ?>

        <?php foreach ($filteredTenants as $t) { ?>
            <?php
                // Détermination de l'état du tenant pour stylisation visuelle de la carte
                $isReadOnly = $t['isReadOnly'];
            $isGrace = $t['licenseStatus'] === 'grace_period';
            ?>
            <div class="p-5 rounded-3xl bg-white dark:bg-zinc-900/70 border transition flex flex-col justify-between space-y-4 shadow-sm <?= $isReadOnly ? 'border-rose-300 dark:border-red-900/50 bg-rose-50/50 dark:bg-red-950/10' : ($isGrace ? 'border-amber-300 dark:border-amber-800/60 bg-amber-50/50 dark:bg-amber-950/10' : 'border-slate-200 dark:border-zinc-800/80 hover:border-slate-300 dark:hover:border-zinc-700') ?>">
                
                <div class="space-y-2">
                    <!-- En-tête de la carte : Nom de la résidence et badge de validité -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm tracking-tight"><?= htmlspecialchars($t['nom']) ?></h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400"><?= htmlspecialchars($t['ville']) ?> &bull; <?= htmlspecialchars($t['code_unique']) ?></p>
                        </div>
                        
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $isReadOnly ? 'bg-rose-100 text-rose-700 dark:bg-red-950 dark:text-red-400 border border-rose-200' : ($isGrace ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200') ?>">
                            <?= htmlspecialchars($t['statusLabel']) ?>
                        </span>
                    </div>

                    <!-- Bannière d'état de la licence commerciale -->
                    <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-zinc-950/80 border border-slate-200 dark:border-zinc-800/60 space-y-1 text-[11px]">
                        <div class="flex items-center justify-between text-slate-500 dark:text-zinc-400">
                            <span>Licence (<?= $t['license_duration_months'] ?> mois) :</span>
                            <span class="font-mono text-slate-800 dark:text-zinc-300">Exp. <?= htmlspecialchars($t['license_expiry_date']) ?></span>
                        </div>

                        <?php if ($isGrace) { ?>
                            <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold flex items-center gap-1">
                                <span>⚠️ Période de grâce : <?= $t['graceDaysRemaining'] ?> jours restants.</span>
                            </div>
                        <?php } ?>

                        <?php if ($isReadOnly) { ?>
                            <div class="text-[10px] text-rose-600 dark:text-red-400 font-bold flex items-center gap-1">
                                <span>🔒 Mode Lecture Seule actif.</span>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Détails techniques : Syndic assigné et fichier SQLite physique -->
                <div class="grid grid-cols-2 gap-2 text-xs py-1 border-t border-b border-slate-100 dark:border-zinc-800/50">
                    <div>
                        <span class="text-slate-400 dark:text-zinc-500 block text-[10px]">Syndic Responsable</span>
                        <span class="font-medium text-slate-800 dark:text-zinc-200 truncate block"><?= htmlspecialchars($t['nom_syndic']) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-zinc-500 block text-[10px]">Base Dédiée</span>
                        <span class="font-mono text-slate-700 dark:text-zinc-300 text-[11px] truncate block"><?= htmlspecialchars($t['dbFile']) ?></span>
                    </div>
                </div>

                <!-- URL DE CONNEXION DU SYNDIC ADMIN (AVEC LE GUID DU TENANT) -->
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-zinc-950/90 border border-slate-200 dark:border-zinc-800/80 space-y-2">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-800 dark:text-zinc-200 flex items-center gap-1">
                            <span>🌐</span>
                            <span>URL Accès Syndic (GUID) :</span>
                        </span>
                        <!-- Bouton de copie automatique de l'URL du tenant dans le presse-papier -->
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('<?= htmlspecialchars($t['syndicLoginUrl']) ?>'); alert('URL copiée !');"
                            class="px-2 py-0.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm"
                            title="Copier l'URL officielle de connexion"
                        >
                            <span>📋 Copier</span>
                        </button>
                    </div>

                    <div class="px-2.5 py-1.5 rounded-xl bg-white dark:bg-black/60 border border-slate-200 dark:border-zinc-800 text-[11px] font-mono text-blue-600 dark:text-blue-400 break-all select-all font-semibold">
                        <?= htmlspecialchars($t['syndicLoginUrl']) ?>
                    </div>

                    <div class="flex items-center justify-between text-[10px] text-slate-400 dark:text-zinc-500 pt-0.5">
                        <span>GUID : <strong class="text-slate-600 dark:text-zinc-400 font-mono"><?= htmlspecialchars($t['id']) ?></strong></span>
                        <a
                            href="<?= htmlspecialchars($t['syndicLoginUrl']) ?>"
                            target="_blank"
                            class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-0.5 font-semibold"
                            title="Accéder au portail de la résidence"
                        >
                            <span>Ouvrir ↗</span>
                        </a>
                    </div>
                </div>

                <!-- ACTIONS D'ADMINISTRATION SUR LE TENANT -->
                <div class="space-y-2 pt-1">
                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                        <!-- Boutons de renouvellement de licence -->
                        <div class="flex gap-1">
                            <a
                                href="actions/renew.php?id=<?= urlencode($t['id']) ?>&months=6"
                                class="w-full py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold transition text-center"
                                title="Prolonger de +6 mois"
                            >
                                +6 Mois
                            </a>
                            <a
                                href="actions/renew.php?id=<?= urlencode($t['id']) ?>&months=12"
                                class="w-full py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 font-semibold transition text-center"
                                title="Prolonger de +12 mois (1 An)"
                            >
                                +1 An
                            </a>
                        </div>

                        <!-- Bascule Verrouillage / Déverrouillage Lecture Seule -->
                        <a
                            href="actions/toggle_readonly.php?id=<?= urlencode($t['id']) ?>&locked=<?= $isReadOnly ? '0' : '1' ?>"
                            class="w-full py-1.5 rounded-lg font-bold border transition text-center flex items-center justify-center gap-1 <?= $isReadOnly ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-red-950/60 dark:text-red-400 border-rose-300' ?>"
                        >
                            <?= $isReadOnly ? '🔓 Déverrouiller' : '🔒 Lecture Seule' ?>
                        </a>
                    </div>

                    <!-- Téléchargement DB et déclencheur de suppression -->
                    <div class="flex items-center justify-between pt-1 text-[11px]">
                        <div class="flex items-center gap-2">
                            <a
                                href="actions/export_db.php?id=<?= urlencode($t['id']) ?>"
                                class="text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 flex items-center gap-1 transition"
                                title="Télécharger la base SQLite"
                            >
                                <span>📥 Export DB</span>
                            </a>

                            <!-- Bouton ouvrant la modale de suppression définitive -->
                            <button
                                type="button"
                                onclick="openDeleteModal('<?= htmlspecialchars($t['id']) ?>', '<?= htmlspecialchars(addslashes($t['nom'])) ?>')"
                                class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 flex items-center gap-0.5 transition font-semibold"
                                title="Supprimer définitivement la copropriété"
                            >
                                <span>🗑 Supprimer</span>
                            </button>
                        </div>

                        <!-- Métriques taille disque et volume de lots -->
                        <span class="text-slate-400 dark:text-zinc-500 font-mono text-[10px]">
                            <?= $t['metrics']['sizeKB'] ?> KB &bull; <?= $t['metrics']['lotsCount'] ?> lots
                        </span>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- JOURNAL D'AUDIT MASTER (Dernières opérations de la plateforme) -->
    <?php if (! empty($stats['logs'])) { ?>
        <div class="p-5 rounded-3xl bg-white dark:bg-zinc-900/50 border border-slate-200 dark:border-zinc-800/80 shadow-sm space-y-3 transition-colors">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 flex items-center gap-2">
                    <span>📋 Journal d'Audit des Événements Plateforme</span>
                </h4>
                <span class="text-[10px] text-slate-400 dark:text-zinc-500 font-mono">Dernières opérations</span>
            </div>
            <div class="space-y-1.5 max-h-44 overflow-y-auto font-mono text-[11px] text-slate-600 dark:text-zinc-400">
                <?php foreach (array_slice($stats['logs'], 0, 6) as $log) { ?>
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-zinc-950/60 border border-slate-200 dark:border-zinc-900">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">[<?= htmlspecialchars($log['action']) ?>]</span>
                            <span class="text-slate-800 dark:text-zinc-300"><?= htmlspecialchars($log['details']) ?></span>
                        </div>
                        <span class="text-[10px] text-slate-400 dark:text-zinc-600"><?= date('H:i:s d/m', strtotime($log['timestamp'])) ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</main>

<!-- MODALE DE PROVISIONNEMENT D'UNE NOUVELLE RÉSIDENCE (CRÉATION BASE VIERGE & GUID) -->
<div id="modal-provision" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
    <div class="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-5 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800/80">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold">
                    🏢
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Provisionner une Nouvelle Résidence</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">Génération automatique du GUID v4 & Base SQLite vierge</p>
                </div>
            </div>
            <button onclick="document.getElementById('modal-provision').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl transition">
                &times;
            </button>
        </div>

        <form action="actions/provision.php" method="POST" class="space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Nom de la Copropriété *</label>
                    <input
                        type="text"
                        name="nom"
                        required
                        placeholder="Ex: Résidence Palmier d'Or"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Ville du Royaume *</label>
                    <select
                        name="ville"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white focus:border-blue-500 focus:outline-none"
                    >
                        <option value="Casablanca">Casablanca</option>
                        <option value="Rabat">Rabat</option>
                        <option value="Tanger">Tanger</option>
                        <option value="Marrakech">Marrakech</option>
                        <option value="Agadir">Agadir</option>
                        <option value="Fès">Fès</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Durée Licence Initiale *</label>
                    <select
                        name="license_duration_months"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white font-bold focus:border-blue-500 focus:outline-none"
                    >
                        <option value="6">6 Mois (+ 1 mois de grâce)</option>
                        <option value="12" selected>1 An (12 mois + 1 mois de grâce)</option>
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Adresse Complète</label>
                    <input
                        type="text"
                        name="adresse"
                        placeholder="Ex: 45 Boulevard d'Anfa, Quartier Gauthier"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>

            <!-- Identifiants du Syndic Administrateur Dédié -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900/50 border border-slate-200 dark:border-zinc-800/80 space-y-3">
                <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>👤 Compte Syndic Administrateur Créé dans la Base Vierge</span>
                </h4>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Nom & Prénom du Syndic *</label>
                        <input
                            type="text"
                            name="nom_syndic"
                            required
                            placeholder="Ex: M. Reda CHRAIBI"
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Email du Syndic (Identifiant) *</label>
                        <input
                            type="email"
                            name="email_syndic"
                            required
                            placeholder="syndic.residence@gmail.com"
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Mot de Passe Initial *</label>
                        <input
                            type="text"
                            name="password_syndic"
                            required
                            value="syndic2026"
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white font-mono focus:border-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Téléphone Portable</label>
                        <input
                            type="text"
                            name="telephone_syndic"
                            value="+212 6 "
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white focus:border-blue-500 focus:outline-none"
                        />
                    </div>
                </div>
            </div>

            <!-- Coordonnées Bancaires de Départ -->
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">RIB Bancaire du Syndicat (24 chiffres) *</label>
                    <input
                        type="text"
                        name="rib_bancaire"
                        required
                        value="011 780 0000 123456789012 34"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white font-mono focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Établissement Bancaire</label>
                    <input
                        type="text"
                        name="banque"
                        value="Attijariwafa Bank"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Solde Bancaire Initial (MAD)</label>
                    <input
                        type="number"
                        name="solde_banque_initial"
                        value="0"
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white font-bold focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-200 dark:border-zinc-800/80">
                <button
                    type="button"
                    onclick="document.getElementById('modal-provision').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white bg-slate-100 dark:bg-zinc-900 transition"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition flex items-center gap-1.5 shadow-md"
                >
                    <span>Générer Base Vierge & Activer</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODALE DE SUPPRESSION DÉFINITIVE D'UNE COPROPRIÉTÉ -->
<div id="modal-delete" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
    <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-rose-300 dark:border-red-900/80 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-2 text-rose-600 dark:text-red-400 font-bold text-sm">
                <span>⚠️</span>
                <span>Suppression Définitive de la Copropriété</span>
            </div>
            <button onclick="document.getElementById('modal-delete').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">&times;</button>
        </div>

        <p class="text-xs text-slate-600 dark:text-zinc-300">
            Vous êtes sur le point de supprimer la copropriété <strong id="delete-tenant-name" class="text-rose-600"></strong>.
        </p>

        <div class="p-3 rounded-xl bg-rose-50 dark:bg-red-950/40 border border-rose-200 dark:border-red-800/60 text-rose-800 dark:text-red-300 text-[11px] space-y-1">
            <p><strong>ATTENTION :</strong></p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>Le fichier SQLite physique dédié sera supprimé du disque.</li>
                <li>Tous les comptes syndics, résidents, quittances et historiques seront effacés.</li>
                <li>Cette action est <strong>irréversible</strong>.</li>
            </ul>
        </div>

        <form action="actions/delete_tenant.php" method="POST" class="space-y-3 text-xs">
            <input type="hidden" name="id" id="delete-tenant-id" value="">
            <div>
                <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">
                    Tapez <span class="font-mono text-rose-600 font-bold">DELETE</span> pour confirmer :
                </label>
                <input
                    type="text"
                    name="confirm"
                    required
                    placeholder="DELETE"
                    class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl text-slate-900 dark:text-white font-mono focus:border-rose-500 focus:outline-none"
                />
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button
                    type="button"
                    onclick="document.getElementById('modal-delete').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white bg-slate-100 dark:bg-zinc-900"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 transition shadow-md"
                >
                    Supprimer Définitivement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODALE DE SAUVEGARDE GLOBALE ZIP DE TOUTES LES BASES -->
<div id="modal-backup" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
    <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-2 text-amber-500 font-bold text-sm">
                <span>🔒</span>
                <span>Sauvegarde Globale des Bases de Données</span>
            </div>
            <button onclick="document.getElementById('modal-backup').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">&times;</button>
        </div>

        <p class="text-xs text-slate-600 dark:text-zinc-300">
            Cette opération exporte le registre maître <code class="font-mono text-blue-600">master.sqlite</code> ainsi que toutes les bases de données SQLite partitionnées de chaque copropriété dans une archive ZIP téléchargeable.
        </p>

        <div class="pt-2 flex items-center justify-end gap-2">
            <button
                type="button"
                onclick="document.getElementById('modal-backup').classList.add('hidden')"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white bg-slate-100 dark:bg-zinc-900"
            >
                Annuler
            </button>
            <a
                href="actions/backup_zip.php"
                class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 transition shadow-md flex items-center gap-1.5"
            >
                <span>Télécharger l'Archive ZIP</span>
            </a>
        </div>
    </div>
</div>

<!-- Script d'ouverture dynamique de la modale de suppression -->
<script>
    function openDeleteModal(tenantId, tenantName) {
        document.getElementById('delete-tenant-id').value = tenantId;
        document.getElementById('delete-tenant-name').textContent = tenantName;
        document.getElementById('modal-delete').classList.remove('hidden');
    }
</script>

<?php
// Inclusion du pied de page officiel Super-Admin
require_once __DIR__.'/includes/footer.php';
?>

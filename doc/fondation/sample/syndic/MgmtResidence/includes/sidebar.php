<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL SYNDIC : MENU LATÉRAL DE NAVIGATION & RBAC DÉLÉGUÉ
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE ARCHITECTURAL ET CONTRÔLE D'ACCÈS RBAC :
 * ----------------------------------------------------------------------------
 * Ce module génère la barre de navigation latérale de l'espace d'administration.
 * Il assure le partitionnement strict des privilèges entre l'administrateur
 * syndic et les délégués du bureau syndical (vice-syndic, trésorier, secrétaire).
 *
 * Règles d'habilitation dynamique :
 * - Rôle 'syndic' (Administrateur principal) : Accès sans restriction à l'ensemble
 *   des 15 modules et des fonctions de paramétrage.
 * - Rôle 'resident' (Délégué mandaté) : Filtrage granulaire des rubriques
 *   selon les permissions accordées en base (colonne JSON `permissions_json`).
 * - Masquage automatique : Toute section thématique ne contenant aucun module
 *   autorisé pour le compte actif est automatiquement éludée du menu.
 * - Passerelle bidirectionnelle : Un délégué bénéficie d'un bouton de bascule
 *   immédiat vers son cockpit de copropriétaire privé (/MgmtResident/).
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DU CONTEXTE DE SESSION ET DES PRIVILÈGES UTILISATEUR
// ============================================================================

require_once __DIR__.'/brand.php';
require_once __DIR__.'/tenant_db.php';

/**
 * GUID unique du tenant actif résolu depuis l'URL ou le cookie de session.
 *
 * @var string $guid
 */
$guid = TenantDB::resolveGuid();

/**
 * Identifiant du module actuellement sélectionné pour mise en surbrillance visuelle.
 *
 * @var string $currentPage
 */
$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Utilisateur authentifié en session.
 *
 * @var array<string, mixed> $user
 */
$user = getCurrentUser();

/**
 * Statut de verrouillage administratif de la copropriété (mode lecture seule).
 *
 * @var bool $isReadOnly
 */
$isReadOnly = TenantDB::isReadOnly();

/**
 * Indicateur précisant si l'utilisateur est un résident titulaire d'une délégation.
 *
 * @var bool $isDelegate
 */
$isDelegate = ! empty($user['delegate']);

/**
 * Intitulé du mandat syndical délégué (ex: "Vice-Syndic Adjoint", "Comptable").
 *
 * @var string|null $delegateRole
 */
$delegateRole = $isDelegate ? ($user['delegate']['role_label'] ?? 'Délégué') : null;

// ============================================================================
// 2. ARBORESCENCE COMPLÈTE DES SECTIONS DE PILOTAGE DE LA COPROPRIÉTÉ
// ============================================================================

/**
 * Définition hiérarchique des rubriques et des modules avec leurs métadonnées.
 *
 * @var array<int, array{title: string, items: array<int, array<string, string>>}> $navSections
 */
$navSections = [
    [
        'title' => 'PILOTAGE',
        'items' => [
            ['id' => 'dashboard', 'label' => 'Cockpit Financier', 'icon' => '📊'],
            ['id' => 'annexes',   'label' => 'Annexes Légales (1 à 5)', 'icon' => '📜', 'badge' => 'Loi 18-00'],
        ],
    ],
    [
        'title' => 'RÉSIDENTS & GOUVERNANCE',
        'items' => [
            ['id' => 'delegues',        'label' => 'Bureau Syndical & Délégués', 'icon' => '👑', 'badge' => 'Gouvernance'],
            ['id' => 'coproprietaires', 'label' => 'Copropriétaires',            'icon' => '👥'],
            ['id' => 'lots',            'label' => 'Lots & Tantièmes',           'icon' => '🏠'],
            ['id' => 'assemblees',      'label' => 'Assemblées Générales',       'icon' => '🗳️'],
            ['id' => 'reclamations',    'label' => 'Tickets & Réclamations',     'icon' => '🔧'],
            ['id' => 'projets',         'label' => 'Projets & Chantiers',        'icon' => '🏗️'],
        ],
    ],
    [
        'title' => 'FINANCES & RECOUVREMENT',
        'items' => [
            ['id' => 'appels',    'label' => 'Appels de Fonds',             'icon' => '💳'],
            ['id' => 'paiements', 'label' => 'Encaissements & Quittances',  'icon' => '🧾'],
            ['id' => 'relances',  'label' => 'Impayés & Contentieux',       'icon' => '⚠️'],
        ],
    ],
    [
        'title' => 'EXPLOITATION & PRESTATAIRES',
        'items' => [
            ['id' => 'depenses',     'label' => 'Dépenses & Factures',          'icon' => '💸'],
            ['id' => 'fournisseurs', 'label' => 'Prestataires & Fournisseurs',  'icon' => '🤝'],
            ['id' => 'carnet',       'label' => "Carnet d'Entretien",           'icon' => '🛠️'],
        ],
    ],
    [
        'title' => 'CONFIGURATION',
        'items' => [
            ['id' => 'settings', 'label' => 'Paramètres & Logo', 'icon' => '⚙️'],
        ],
    ],
];
?>

<!-- ========================================================================= -->
<!-- BARRE DE NAVIGATION LATÉRALE FLOTTANTE / RESPONSIVE (MAIN SIDEBAR)        -->
<!-- ========================================================================= -->
<aside id="main-sidebar" class="fixed lg:static top-0 bottom-0 left-0 z-50 w-64 bg-white dark:bg-[#1A0526] border-r border-[#F0E4DC] dark:border-[#3D154F] text-slate-800 dark:text-slate-200 flex flex-col justify-between transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0 shadow-xl lg:shadow-none">
    <div class="flex flex-col flex-1 min-h-0">

        <!-- ================================================================= -->
        <!-- BANDEAU SUPÉRIEUR DE MARQUE & IDENTITÉ DE LA COPROPRIÉTÉ          -->
        <!-- ================================================================= -->
        <div class="p-4 border-b border-[#F0E4DC] dark:border-[#3D154F] space-y-3">
            <div class="flex items-center gap-2.5">
                <!-- Rendu du logo ou monogramme officiel avec fallback garanti -->
                <?= renderResidenceLogoBadge($residence, 34, false) ?>
                <div class="min-w-0 flex-1">
                    <div class="font-bold text-sm text-slate-900 dark:text-white tracking-tight truncate" title="<?= htmlspecialchars($residence['nom'] ?? 'Résidence') ?>">
                        <?= htmlspecialchars($residence['nom'] ?? 'Résidence') ?>
                    </div>
                    <div class="text-[10.5px] text-slate-500 dark:text-slate-400 truncate">
                        <?= htmlspecialchars($residence['ville'] ?? 'Maroc') ?> &bull; <?= htmlspecialchars($residence['code_unique'] ?? '') ?>
                    </div>
                </div>
            </div>

            <!-- Badge Co-Branding Bayan Gestion (Éditeur Plateforme) -->
            <div class="p-2 rounded-xl bg-gradient-to-r from-[#D91C6E]/10 to-[#F27835]/10 border border-[#D91C6E]/20 text-[10.5px] flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-medium">Créé par</span>
                <?= getBayanLogoSvg(14, 'auto', false) ?>
            </div>

            <?php if ($isDelegate) { ?>
                <!-- Badge de distinction pour les membres délégués du bureau syndical -->
                <div class="p-2.5 rounded-xl bg-[#D91C6E]/10 border border-[#D91C6E]/25 text-[#D91C6E] dark:text-[#F26968] text-[11px] font-bold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-1.5 truncate">
                        <span aria-hidden="true">👑</span>
                        <span class="truncate"><?= htmlspecialchars((string) $delegateRole) ?></span>
                    </div>
                    <span class="text-[9px] uppercase px-1.5 py-0.5 rounded bg-[#D91C6E]/20 font-extrabold shrink-0">Délégation</span>
                </div>
            <?php } ?>

            <?php if ($isReadOnly) { ?>
                <!-- Badge d'avertissement de mode lecture seule -->
                <div class="p-2.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-[11px] font-bold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-1.5">
                        <span aria-hidden="true">🔒</span>
                        <span>Lecture Seule</span>
                    </div>
                    <span class="text-[9px] uppercase px-1.5 py-0.5 rounded bg-rose-500/20 font-extrabold">Verrouillé</span>
                </div>
            <?php } ?>
        </div>

        <!-- ================================================================= -->
        <!-- LIENS DE NAVIGATION FILTRÉS PAR LES PERMISSIONS RBAC              -->
        <!-- ================================================================= -->
        <div class="flex-1 p-3 space-y-4 overflow-y-auto">
            <?php foreach ($navSections as $sec) { ?>
                <?php
                // Filtrage strict des éléments de menu selon le rôle et les permissions
                $filteredItems = array_filter($sec['items'], function ($item) use ($user) {
                    if (($user['role'] ?? '') === 'syndic') {
                        return true;
                    }
                    // Pour le module délégués, autoriser si syndic ou si le délégué a la permission settings
                    if ($item['id'] === 'delegues') {
                        return TenantDB::hasPermission($user, 'delegues') || TenantDB::hasPermission($user, 'settings');
                    }

                    return TenantDB::hasPermission($user, $item['id']);
                });

                // Si aucun lien n'est autorisé pour l'utilisateur dans cette section, masquer le groupe entier
                if (empty($filteredItems)) {
                    continue;
                }
                ?>
                <div class="space-y-0.5">
                    <div class="px-2 text-[9.5px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">
                        <?= htmlspecialchars($sec['title']) ?>
                    </div>
                    <?php foreach ($filteredItems as $item) { ?>
                        <?php $isActive = ($currentPage === $item['id']); ?>
                        <a
                            href="index.php?tenant=<?= urlencode($guid) ?>&page=<?= urlencode($item['id']) ?>"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs font-medium transition <?= $isActive ? 'bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white font-bold shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-[#FDF8F5] dark:hover:bg-[#250832] hover:text-[#D91C6E] dark:hover:text-[#F26968]' ?>"
                        >
                            <div class="flex items-center gap-2.5 truncate">
                                <span><?= $item['icon'] ?></span>
                                <span class="truncate"><?= htmlspecialchars($item['label']) ?></span>
                            </div>
                            <?php if (! empty($item['badge'])) { ?>
                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold shrink-0 <?= $isActive ? 'bg-white/20 text-white' : 'bg-[#D91C6E]/10 text-[#D91C6E] dark:text-[#F26968]' ?>">
                                    <?= htmlspecialchars($item['badge']) ?>
                                </span>
                            <?php } ?>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- PIED DU MENU : PROFIL CONNECTÉ, PASSERELLE RÉSIDENT & DÉCONNEXION     -->
    <!-- ===================================================================== -->
    <div class="p-3 border-t border-[#F0E4DC] dark:border-[#3D154F] space-y-2">
        <div class="px-2 py-1 space-y-0.5">
            <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate">
                <?= htmlspecialchars($user['nom'] ?? 'Utilisateur') ?>
            </div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <?php if ($isDelegate) { ?>
                    <span class="text-[#D91C6E] dark:text-[#F26968] font-bold">👑 <?= htmlspecialchars((string) $delegateRole) ?></span>
                <?php } else { ?>
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">⚡ Administrateur Syndic</span>
                <?php } ?>
            </div>
        </div>

        <?php if ($isDelegate) { ?>
            <!-- Raccourci direct vers l'Espace Résident pour les copropriétaires délégués -->
            <a
                href="/Syndic/MgmtResident/<?= urlencode($guid) ?>/index.php"
                class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold border border-emerald-500/20 transition"
                title="Accéder à mon espace copropriétaire personnel"
            >
                <span>👤 Mon Espace Résident ➔</span>
            </a>
        <?php } ?>

        <!-- Bouton de déconnexion sécurisée -->
        <a
            href="logout.php?tenant=<?= urlencode($guid) ?>"
            class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-semibold border border-rose-500/20 transition"
        >
            <span>🚪 Déconnexion</span>
        </a>
    </div>
</aside>

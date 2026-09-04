<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : MENU LATÉRAL DE NAVIGATION (SIDEBAR)
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE DU MODULE ET ERGONOMIE DU COCKPIT RÉSIDENT :
 * ----------------------------------------------------------------------------
 * Ce module affiche la barre latérale de navigation de l'espace privatif résident.
 *
 * Caractéristiques ergonomiques et sécuritaires :
 * - Navigation thématique divisée en 4 sections claires :
 *   1. Mon Cockpit (Accueil et synthèse comptable)
 *   2. Mes Charges & Finances (Quittances libératoires certifiées Art. 25)
 *   3. Services & Assistance (Signalements de pannes et réclamations)
 *   4. Transparence Copropriété (PV d'AG, carnet d'entretien, grands travaux, fiche immeuble)
 * - Passerelle Bureau Syndic : Si le copropriétaire est mandaté au sein du
 *   bureau syndical (délégué), un lien d'accès privilégié vers /MgmtResidence/
 *   est mis en évidence au sommet du menu.
 * - Profil résident : Rappel du numéro de lot principal et de la quote-part
 *   exacte en tantièmes sur les 10 000 tantièmes de la copropriété.
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DU CONTEXTE DE SESSION ET DES INFORMATIONS DU RÉSIDENT
// ============================================================================

require_once dirname(__DIR__, 2).'/MgmtResidence/includes/brand.php';

/**
 * GUID unique du tenant actif.
 *
 * @var string $guid
 */
$guid = TenantDB::resolveGuid();

/**
 * Identifiant du module actuellement sélectionné pour l'état actif.
 *
 * @var string $currentPage
 */
$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Utilisateur copropriétaire en session.
 *
 * @var array<string, mixed> $user
 */
$user = getCurrentResidentUser();

/**
 * Statut de verrouillage administratif de la résidence.
 *
 * @var bool $isReadOnly
 */
$isReadOnly = TenantDB::isReadOnly();

/**
 * Identifiant primaire de copropriétaire rattaché au compte.
 *
 * @var int|null $copId
 */
$copId = $user['coproprietaire_id'] ?? null;

/**
 * Fiche détaillée du copropriétaire.
 *
 * @var array<string, mixed>|null $copInfo
 */
$copInfo = ResidentDB::getCoproprietaireInfo($copId);

/**
 * Liste des lots détenus par le copropriétaire.
 *
 * @var array<int, array<string, mixed>> $residentLots
 */
$residentLots = ResidentDB::getResidentLots($copId);

/**
 * Situation financière personnelle calculée.
 *
 * @var array<string, mixed> $situation
 */
$situation = ResidentDB::getResidentSituation($copId, (int) ($_GET['exercice'] ?? 2025));

/**
 * Mandat délégué éventuel accordé au résident.
 *
 * @var array<string, mixed>|null $residentDelegate
 */
$residentDelegate = TenantDB::getDelegateByUserId($user['id']);

/**
 * Structure de navigation de l'espace résident.
 *
 * @var array<int, array{title: string, items: array<int, array<string, string>>}> $navSections
 */
$navSections = [
    [
        'title' => 'MON COCKPIT',
        'items' => [
            ['id' => 'dashboard', 'label' => 'Mon Espace Résident', 'icon' => '🏠'],
        ],
    ],
    [
        'title' => 'MES CHARGES & FINANCES',
        'items' => [
            ['id' => 'paiements', 'label' => 'Mes Quittances Libératoires', 'icon' => '🧾', 'badge' => 'Loi 18-00'],
        ],
    ],
    [
        'title' => 'SERVICES & ASSISTANCE',
        'items' => [
            ['id' => 'reclamations', 'label' => 'Mes Signalements & Pannes', 'icon' => '🛠️'],
        ],
    ],
    [
        'title' => 'TRANSPARENCE COPROPRIÉTÉ',
        'items' => [
            ['id' => 'assemblees', 'label' => 'Assemblées Générales & PV', 'icon' => '📋'],
            ['id' => 'projets',    'label' => 'Grands Travaux & Chantiers', 'icon' => '🏗️'],
            ['id' => 'carnet',     'label' => "Carnet d'Entretien Immeuble", 'icon' => '📖'],
            ['id' => 'immeuble',   'label' => 'Mon Immeuble & Syndic',       'icon' => '🏢'],
        ],
    ],
];
?>

<!-- ========================================================================= -->
<!-- BARRE DE NAVIGATION LATÉRALE DU RÉSIDENT (RESIDENT SIDEBAR)               -->
<!-- ========================================================================= -->
<aside id="resident-sidebar" class="fixed lg:static top-0 bottom-0 left-0 z-50 w-64 bg-white dark:bg-[#1A0526] border-r border-[#F0E4DC] dark:border-[#3D154F] text-slate-800 dark:text-slate-200 flex flex-col justify-between transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0 shadow-lg lg:shadow-none">
    <div class="flex flex-col flex-1 min-h-0">

        <!-- ================================================================= -->
        <!-- BANDEAU SUPÉRIEUR DE MARQUE & IDENTITÉ DE LA COPROPRIÉTÉ          -->
        <!-- ================================================================= -->
        <div class="p-4 border-b border-[#F0E4DC] dark:border-[#3D154F] space-y-3">
            <div class="flex items-center gap-2.5">
                <!-- Rendu officiel du blason ou logo de l'immeuble -->
                <?= renderResidenceLogoBadge($residence, 34, false) ?>
                <div class="min-w-0 flex-1">
                    <div class="font-bold text-sm text-slate-900 dark:text-white tracking-tight truncate" title="<?= htmlspecialchars($residence['nom'] ?? 'Résidence') ?>">
                        <?= htmlspecialchars($residence['nom'] ?? 'Copropriété') ?>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#D91C6E]"></span>
                        <span class="truncate"><?= htmlspecialchars($residence['ville'] ?? 'Maroc') ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/30 uppercase">
                    👤 Espace Résident
                </span>
                <?php if ($isReadOnly) { ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 uppercase" title="Mode consultation seule">
                        🔒 Lecture
                    </span>
                <?php } ?>
            </div>

            <?php if ($residentDelegate) { ?>
                <!-- Bouton d'accès réservé aux délégués du bureau syndical -->
                <div class="pt-1">
                    <a
                        href="/Syndic/MgmtResidence/<?= urlencode($guid) ?>/index.php"
                        class="block w-full py-2 px-2.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white text-[11px] font-bold text-center transition shadow-md"
                        title="Accéder aux modules administratifs de la résidence"
                    >
                        👑 Bureau Syndic (<?= htmlspecialchars((string) $residentDelegate['role_label']) ?>) ➔
                    </a>
                </div>
            <?php } ?>
        </div>

        <!-- ================================================================= -->
        <!-- LIENS DE NAVIGATION THÉMATIQUES                                   -->
        <!-- ================================================================= -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-4 text-xs">
            <?php foreach ($navSections as $section) { ?>
                <div class="space-y-1">
                    <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400/80">
                        <?= htmlspecialchars($section['title']) ?>
                    </div>
                    <?php foreach ($section['items'] as $item) { ?>
                        <?php $active = ($currentPage === $item['id']); ?>
                        <a
                            href="index.php?tenant=<?= urlencode($guid) ?>&page=<?= urlencode($item['id']) ?>&exercice=<?= $selectedExercice ?>"
                            class="flex items-center justify-between px-3 py-2 rounded-xl font-medium transition <?= $active ? 'bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white shadow-md' : 'text-slate-600 dark:text-slate-300 hover:bg-[#FDF8F5] dark:hover:bg-[#250832]' ?>"
                        >
                            <div class="flex items-center gap-2.5 truncate">
                                <span class="text-sm"><?= $item['icon'] ?></span>
                                <span class="truncate"><?= htmlspecialchars($item['label']) ?></span>
                            </div>
                            <?php if (! empty($item['badge'])) { ?>
                                <span class="text-[9px] px-1.5 py-0.2 rounded-full font-bold uppercase <?= $active ? 'bg-white/20 text-white' : 'bg-[#D91C6E]/10 text-[#D91C6E] dark:text-[#F26968]' ?>">
                                    <?= htmlspecialchars($item['badge']) ?>
                                </span>
                            <?php } ?>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </nav>
    </div>

    <!-- ===================================================================== -->
    <!-- PIED DU MENU : PROFIL COPROPRIÉTAIRE, QUOTE-PART & DÉCONNEXION        -->
    <!-- ===================================================================== -->
    <div class="p-3 border-t border-[#F0E4DC] dark:border-[#3D154F] bg-[#FDF8F5]/80 dark:bg-[#15021E]/60 space-y-2">
        <div class="p-2.5 rounded-2xl bg-white dark:bg-[#22082E] border border-[#F0E4DC] dark:border-[#3D154F] flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <!-- Avatar avec initiale majuscule du copropriétaire -->
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                    <?= strtoupper(substr((string) ($user['nom'] ?? 'R'), 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                        <?= htmlspecialchars((string) ($user['nom'] ?? 'Copropriétaire')) ?>
                    </div>
                    <div class="text-[10px] text-slate-400 dark:text-slate-400 font-mono truncate">
                        Lot #<?= htmlspecialchars((string) ($residentLots[0]['numero'] ?? '101')) ?> &bull; <?= $situation['residentTantiemes'] ?> / <?= $situation['totalResidenceTantiemes'] ?>
                    </div>
                </div>
            </div>

            <!-- Bouton de déconnexion sécurisée de la session résident -->
            <a
                href="logout.php?tenant=<?= urlencode($guid) ?>"
                class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition shrink-0"
                title="Se Déconnecter"
            >
                🚪
            </a>
        </div>
    </div>
</aside>

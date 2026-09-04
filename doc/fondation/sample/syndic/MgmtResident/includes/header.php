<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : EN-TÊTE GLOBAL & STRUCTURE COMMUNE
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE ARCHITECTURAL & SÉCURITÉ DE LA SESSION RÉSIDENT :
 * ----------------------------------------------------------------------------
 * Ce fichier initialise le gabarit HTML supérieur de l'espace privatif résident.
 *
 * Traitements exécutés :
 * 1. Vérification de l'authentification du copropriétaire via requireResidentAuth().
 * 2. Isolation des données : Chargement strict des lots et de la situation comptable
 *    liée au copropriétaire connecté ($copId), interdisant toute fuite transverse.
 * 3. Détection de mandat syndical : Si le résident est également membre délégué
 *    du bureau du syndic (vice-syndic, trésorier, etc.), un bouton de bascule vers
 *    le portail administratif /MgmtResidence/ lui est automatiquement proposé.
 * 4. Injection de Tailwind CSS avec la charte officielle Bayan Gestion.
 * 5. Gestion de l'ambiance claire (Primrose) / sombre (Deep Plum) via localStorage.
 */

declare(strict_types=1);

// ============================================================================
// 1. CHARGEMENT DES DÉPENDANCES ET EXTRACTION DU CONTEXTE RÉSIDENT
// ============================================================================

require_once dirname(__DIR__, 2).'/MgmtResidence/includes/tenant_db.php';
require_once dirname(__DIR__, 2).'/MgmtResidence/includes/brand.php';
require_once __DIR__.'/resident_auth.php';
require_once __DIR__.'/resident_db.php';

/**
 * GUID unique du tenant actif résolu depuis la requête ou le cookie de session.
 *
 * @var string $guid
 */
$guid = TenantDB::resolveGuid();

/**
 * Informations administratives et coordonnées de la résidence.
 *
 * @var array<string, mixed> $residence
 */
$residence = TenantDB::getResidence();

/**
 * Utilisateur copropriétaire authentifié en session.
 *
 * @var array<string, mixed> $user
 */
$user = requireResidentAuth();

/**
 * Statut de verrouillage administratif de la copropriété.
 *
 * @var bool $isReadOnly
 */
$isReadOnly = TenantDB::isReadOnly();

/**
 * Identifiant du copropriétaire associé au compte.
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
 * Liste des lots privatifs détenus par le copropriétaire.
 *
 * @var array<int, array<string, mixed>> $residentLots
 */
$residentLots = ResidentDB::getResidentLots($copId);

/**
 * Situation comptable personnelle calculée pour l'exercice.
 *
 * @var array<string, mixed> $situation
 */
$situation = ResidentDB::getResidentSituation($copId, (int) ($_GET['exercice'] ?? 2025));

/**
 * Mandat délégué éventuel accordé à ce compte résident.
 *
 * @var array<string, mixed>|null $residentDelegate
 */
$residentDelegate = TenantDB::getDelegateByUserId($user['id']);

/**
 * Dictionnaire des titres de pages de l'espace résident.
 *
 * @var array<string, string> $pageTitles
 */
$pageTitles = [
    'dashboard' => 'Mon Espace Résident',
    'paiements' => 'Mes Quittances Libératoires',
    'reclamations' => 'Mes Signalements & Incidents',
    'assemblees' => 'Assemblées Générales & PV',
    'projets' => 'Grands Travaux & Chantiers',
    'carnet' => "Carnet d'Entretien de l'Immeuble",
    'immeuble' => 'Fiche Immeuble & Mon Syndic',
];

/**
 * Module actif dans la navigation.
 *
 * @var string $currentPage
 */
$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Titre contextuel de l'onglet.
 *
 * @var string $title
 */
$title = $pageTitles[$currentPage] ?? 'Espace Résident';

/**
 * Exercice comptable sélectionné.
 *
 * @var int $selectedExercice
 */
$selectedExercice = (int) ($_GET['exercice'] ?? 2025);
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($residence['nom'] ?? 'Copropriété') ?> - Bayan Gestion</title>

    <!-- Favicon Officiel Bayan Gestion -->
    <link rel="icon" type="image/svg+xml" href="/Syndic/assets/img/bayan_icon.svg">
    <link rel="alternate icon" type="image/png" sizes="32x32" href="/Syndic/assets/img/bayan_icon_32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/Syndic/assets/img/bayan_icon_apple.png">
    <link rel="shortcut icon" href="/Syndic/favicon.ico">

    <!-- Typographie Poppins Officielle Bayan Gestion -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Framework CSS Tailwind avec Palette Signature -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bayan: {
                            50: '#FDF8F5',
                            100: '#FCEFE9',
                            200: '#F9DDD2',
                            300: '#F5B8A8',
                            400: '#F26968', // Salmon
                            500: '#D91C6E', // Signature Magenta
                            600: '#BF155E',
                            700: '#8A0D44',
                            800: '#4A0826',
                            900: '#1E0427', // Deep Plum
                            orange: '#F27835', // Warm Orange
                            dark: '#14021C',   // Fond sombre profond
                            surface: '#22082E',// Fond des cartes
                            border: '#3D154F'  // Bordure aubergine
                        },
                        brand: {
                            500: '#D91C6E',
                            600: '#BF155E',
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'Segoe UI', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Styles fond d'écran anti-scintillement */
        html.light { background-color: #FDF8F5; color: #1E0427; font-family: 'Poppins', sans-serif; }
        html.dark  { background-color: #14021C; color: #FAF4F8; font-family: 'Poppins', sans-serif; }
    </style>
    <script>
        /**
         * Application immédiate du mode de couleur persisté en localStorage
         */
        const savedTheme = localStorage.getItem('syndic_color_mode') || 'dark';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }

        /**
         * Bascule interactive mode clair / mode sombre
         */
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
                localStorage.setItem('syndic_color_mode', 'light');
            } else {
                document.documentElement.classList.remove('light');
                document.documentElement.classList.add('dark');
                localStorage.setItem('syndic_color_mode', 'dark');
            }
            updateThemeIcons();
        }

        /**
         * Mise à jour des icônes d'ambiance
         */
        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            const suns = document.querySelectorAll('.icon-sun');
            const moons = document.querySelectorAll('.icon-moon');
            suns.forEach(el => el.style.display = isDark ? 'inline-block' : 'none');
            moons.forEach(el => el.style.display = isDark ? 'none' : 'inline-block');
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcons);
    </script>
</head>
<body class="min-h-screen flex bg-[#FDF8F5] dark:bg-[#14021C] text-[#1E0427] dark:text-[#FAF4F8] font-sans transition-colors duration-200 selection:bg-[#D91C6E] selection:text-white">

    <!-- Inclusion de la barre latérale de navigation du résident -->
    <?php require_once __DIR__.'/sidebar.php'; ?>

    <!-- Conteneur principal fluide -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- ================================================================= -->
        <!-- BANDEAU SUPÉRIEUR FLOTTANT (NAVBAR RÉSIDENT)                      -->
        <!-- ================================================================= -->
        <header class="h-16 border-b border-[#F0E4DC] dark:border-[#3D154F] bg-white/95 dark:bg-[#1E0427]/95 backdrop-blur px-4 lg:px-8 flex items-center justify-between sticky top-0 z-30 transition-colors">

            <!-- Côté Gauche : Titre du module et bouton menu mobile -->
            <div class="flex items-center gap-3">
                <button
                    onclick="document.getElementById('resident-sidebar').classList.toggle('-translate-x-full')"
                    class="lg:hidden p-2 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-700 dark:text-slate-200"
                    title="Menu"
                >
                    ☰
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">
                            <?= htmlspecialchars($title) ?>
                        </h1>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/20">
                            Espace Résident
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 hidden sm:block">
                        <?= htmlspecialchars($residence['nom']) ?> &bull; Lot <?= htmlspecialchars((string) ($residentLots[0]['numero'] ?? 'Personnel')) ?>
                    </p>
                </div>
            </div>

            <!-- Côté Droit : Solde comptable en direct, Passerelle Bureau, Filtre Exercice, Thème -->
            <div class="flex items-center gap-2 sm:gap-3">

                <!-- Co-Branding Bayan Gestion -->
                <div class="hidden xl:flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/80 dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] text-[11px]" title="Plateforme créée par Bayan Gestion">
                    <span class="text-slate-400">Créé par</span>
                    <?= getBayanLogoSvg(16, 'auto', false) ?>
                </div>

                <!-- Badge Synthétique de la Situation Comptable Personnelle -->
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs <?= $situation['isAJour'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30' ?>">
                    <span><?= $situation['isAJour'] ? '✅' : '⚠️' ?></span>
                    <span class="font-bold">
                        <?= $situation['isAJour'] ? 'Cotisations À Jour' : 'Reste Dû : '.number_format($situation['soldeDu'], 2, ',', ' ').' MAD' ?>
                    </span>
                </div>

                <?php if ($residentDelegate) { ?>
                    <!-- Passerelle vers les fonctions administratives du bureau syndical -->
                    <a
                        href="/Syndic/MgmtResidence/<?= urlencode($guid) ?>/index.php"
                        class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs shadow-md transition flex items-center gap-1.5"
                        title="Accéder aux modules administratifs délégués"
                    >
                        <span aria-hidden="true">👑</span>
                        <span class="hidden sm:inline">Bureau Syndic (<?= htmlspecialchars((string) $residentDelegate['role_label']) ?>) ➔</span>
                        <span class="sm:hidden">Syndic ➔</span>
                    </a>
                <?php } ?>

                <!-- Sélecteur de l'Exercice Comptable -->
                <form method="GET" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] border border-slate-200 dark:border-[#3D154F] text-xs">
                    <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($currentPage) ?>">
                    <span aria-hidden="true">📅</span>
                    <select
                        name="exercice"
                        onchange="this.form.submit()"
                        class="bg-transparent text-xs font-bold text-slate-900 dark:text-white border-none focus:outline-none cursor-pointer"
                    >
                        <?php foreach ([2024, 2025, 2026] as $y) { ?>
                            <option value="<?= $y ?>" <?= ($selectedExercice ?? 2025) == $y ? 'selected' : '' ?> class="bg-white dark:bg-[#1E0427] text-slate-900 dark:text-white">
                                <?= $y ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>

                <!-- Bascule Mode Clair / Sombre -->
                <button
                    onclick="toggleDarkMode()"
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#250832] dark:hover:bg-[#340c44] border border-slate-200 dark:border-[#3D154F] text-slate-700 dark:text-slate-300 transition"
                    title="Basculer Mode Clair / Sombre"
                >
                    <span class="icon-sun text-amber-500 font-bold" aria-hidden="true">☀️</span>
                    <span class="icon-moon text-[#D91C6E] font-bold" aria-hidden="true">🌙</span>
                </button>
            </div>
        </header>

        <!-- ================================================================= -->
        <!-- CONTENEUR DU CORPS DE PAGE                                        -->
        <!-- ================================================================= -->
        <main class="flex-1 p-4 lg:p-8 max-w-7xl mx-auto w-full">

            <?php if ($isReadOnly) { ?>
                <!-- Grande Bannière d'Avertissement Mode Lecture Seule -->
                <div class="mb-6 p-4.5 rounded-2xl bg-gradient-to-r from-rose-500/15 via-red-500/10 to-amber-500/10 border-2 border-rose-500/40 text-rose-900 dark:text-rose-200 shadow-md backdrop-blur-sm">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xl shrink-0 shadow-inner">
                            🔒
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wide">
                                    Copropriété en Mode Consultation Seule (Licence Plateforme)
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-800 dark:text-rose-200 border border-rose-500/30">
                                    Écriture Verrouillée
                                </span>
                            </div>
                            <p class="text-xs text-rose-800 dark:text-rose-300/90 mt-1 leading-relaxed">
                                La copropriété est actuellement en mode lecture seule. Toutes les données (quittances, situations de compte, PV d'assemblées, carnet d'entretien) restent consultables et téléchargeables. Le dépôt de nouvelles réclamations est temporairement suspendu jusqu'à régularisation auprès de Bayan Gestion.
                            </p>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if (($_GET['error'] ?? '') === 'read_only_mode') { ?>
                <!-- Message d'erreur en cas de tentative d'écriture bloquée -->
                <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-3">
                    <span class="text-lg" aria-hidden="true">⛔</span>
                    <span><strong>Action Interrompue :</strong> L'enregistrement de nouvelles données est désactivé en mode lecture seule.</span>
                </div>
            <?php } ?>

            <?php if (($_GET['success'] ?? '') === 'reclamation_added') { ?>
                <!-- Toast de confirmation après enregistrement d'un incident -->
                <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-3">
                    <span class="text-lg" aria-hidden="true">✅</span>
                    <span><strong>Signalement transmis :</strong> Votre réclamation a bien été envoyée au syndic avec succès.</span>
                </div>
            <?php } ?>

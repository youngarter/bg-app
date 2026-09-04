<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL SYNDIC : EN-TÊTE GLOBAL, THEME & STRUCTURE COMMUNE
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE ARCHITECTURAL & SÉCURITÉ :
 * ----------------------------------------------------------------------------
 * Ce fichier constitue le point d'entrée visuel de toutes les pages de gestion
 * du syndic et des membres du bureau syndical délégué.
 *
 * Traitements exécutés :
 * 1. Initialisation de la connexion sécurisée au tenant SQLite partitionné.
 * 2. Contrôle de session strict et authentification via requireAuth().
 * 3. Calcul en temps réel des soldes de trésorerie du cockpit financier.
 * 4. Détection du mode lecture seule (suspension des licences plateforme).
 * 5. Configuration dynamique de Tailwind CSS avec la palette officielle Bayan Gestion.
 * 6. Gestionnaire de bascule d'ambiance claire / sombre persistée en localStorage.
 */

declare(strict_types=1);

// ============================================================================
// 1. INCLUSION DES DÉPENDANCES ET INITIALISATION DU CONTEXTE TENANT
// ============================================================================

require_once __DIR__.'/tenant_db.php';
require_once __DIR__.'/tenant_auth.php';
require_once __DIR__.'/brand.php';

/**
 * GUID unique du tenant actif résolu depuis les paramètres d'URL ou le cookie.
 *
 * @var string $guid
 */
$guid = TenantDB::resolveGuid();

/**
 * Informations administratives et juridiques de la copropriété courante.
 *
 * @var array<string, mixed> $residence
 */
$residence = TenantDB::getResidence();

/**
 * Utilisateur administrateur syndic ou délégué connecté (session vérifiée).
 *
 * @var array<string, mixed> $user
 */
$user = requireAuth();

/**
 * Indicateurs financiers globaux du cockpit (trésorerie disponible, impayés, etc.).
 *
 * @var array<string, mixed> $cockpit
 */
$cockpit = TenantDB::getFinancialCockpit((int) ($selectedExercice ?? 2025));

/**
 * Drapeau d'état de verrouillage administratif de la copropriété.
 *
 * @var bool $isReadOnly
 */
$isReadOnly = TenantDB::isReadOnly();

/**
 * Répertoire des titres de pages et correspondances d'affichage pour la barre de titre.
 *
 * @var array<string, string> $pageTitles
 */
$pageTitles = [
    'dashboard' => 'Cockpit Financier',
    'annexes' => 'Annexes Légales (1 à 5)',
    'reclamations' => 'Tickets & Réclamations',
    'projets' => 'Projets & Chantiers',
    'coproprietaires' => 'Copropriétaires',
    'lots' => 'Lots & Tantièmes',
    'delegues' => 'Bureau Syndical & Délégués',
    'appels' => 'Appels de Fonds',
    'paiements' => 'Encaissements & Quittances',
    'relances' => 'Impayés & Contentieux',
    'depenses' => 'Dépenses & Factures',
    'fournisseurs' => 'Fournisseurs & Prestataires',
    'carnet' => "Carnet d'Entretien",
    'assemblees' => 'Assemblées Générales',
    'settings' => 'Paramètres de la Résidence',
];

/**
 * Identifiant du module actuellement sélectionné dans le menu ou la requête GET.
 *
 * @var string $currentPage
 */
$currentPage = $_GET['page'] ?? 'dashboard';

/**
 * Titre contextuel de l'onglet et du bandeau supérieur.
 *
 * @var string $title
 */
$title = $pageTitles[$currentPage] ?? 'Gestion de Copropriété';
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($residence['nom'] ?? 'Syndic') ?></title>

    <!-- Favicon Officiel Bayan Gestion -->
    <link rel="icon" type="image/svg+xml" href="/Syndic/assets/img/bayan_icon.svg">
    <link rel="alternate icon" type="image/png" sizes="32x32" href="/Syndic/assets/img/bayan_icon_32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/Syndic/assets/img/bayan_icon_apple.png">
    <link rel="shortcut icon" href="/Syndic/favicon.ico">

    <!-- Typographie Officielle Google Fonts : Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Framework Tailwind CSS via CDN avec extension de la palette chromatique Bayan -->
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
                            400: '#F26968', // Nuance Salmon
                            500: '#D91C6E', // Nuance Signature Magenta
                            600: '#BF155E',
                            700: '#8A0D44',
                            800: '#4A0826',
                            900: '#1E0427', // Nuance Deep Plum
                            orange: '#F27835', // Nuance Warm Orange
                            dark: '#14021C',   // Fond sombre profond
                            surface: '#22082E',// Fond des cartes et conteneurs
                            border: '#3D154F'  // Bordure aubergine discrète
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
        /* Styles de base pour éviter tout saut de contraste au chargement initial */
        html.light { background-color: #FDF8F5; color: #1E0427; font-family: 'Poppins', sans-serif; }
        html.dark  { background-color: #14021C; color: #FAF4F8; font-family: 'Poppins', sans-serif; }
    </style>
    <script>
        /**
         * Script immédiat d'application du thème (évite le phénomène de Flash of Unstyled Content)
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
         * Bascule interactive entre le mode sombre (Deep Plum) et le mode clair (Primrose)
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
         * Synchronise l'affichage des icônes du soleil et de la lune
         */
        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            const suns = document.querySelectorAll('.icon-sun');
            const moons = document.querySelectorAll('.icon-moon');
            suns.forEach(el => el.style.display = isDark ? 'inline-block' : 'none');
            moons.forEach(el => el.style.display = isDark ? 'none' : 'inline-block');
        }

        // Déclenchement automatique au chargement complet du DOM
        document.addEventListener('DOMContentLoaded', updateThemeIcons);
    </script>
</head>
<body class="min-h-screen bg-[#FDF8F5] dark:bg-[#14021C] text-[#1E0427] dark:text-[#FAF4F8] flex font-sans selection:bg-[#D91C6E] selection:text-white transition-colors duration-200">

    <!-- Inclusion de la barre de navigation latérale (Sidebar dynamique) -->
    <?php require_once __DIR__.'/sidebar.php'; ?>

    <!-- Conteneur central fluide pour les modules applicatifs -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- ================================================================= -->
        <!-- HEADER SUPÉRIEUR FLOTTANT (NAVBAR ADMINISTRATIVE)                 -->
        <!-- ================================================================= -->
        <header class="sticky top-0 z-30 bg-white/95 dark:bg-[#1E0427]/95 border-b border-[#F0E4DC] dark:border-[#3D154F] backdrop-blur-md px-4 lg:px-6 py-2.5 flex items-center justify-between transition-colors duration-200">

            <!-- Côté Gauche : Bouton menu mobile, Logo Résidence et Titre -->
            <div class="flex items-center gap-3">
                <button
                    onclick="document.getElementById('main-sidebar').classList.toggle('-translate-x-full')"
                    class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                    title="Afficher/Masquer le menu"
                >
                    ☰
                </button>
                <div class="flex items-center gap-3">
                    <!-- Badge du logo officiel de la copropriété ou placeholder garanti -->
                    <?= renderResidenceLogoBadge($residence, 32, false) ?>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">
                                <?= htmlspecialchars($title) ?>
                            </h1>
                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full bg-[#D91C6E]/10 text-[#D91C6E] dark:text-[#F26968] text-[11px] font-bold border border-[#D91C6E]/20">
                                <?= htmlspecialchars($residence['nom'] ?? 'Résidence') ?>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 hidden sm:block">
                            <?= htmlspecialchars($residence['ville'] ?? '') ?> &bull; <?= htmlspecialchars($residence['code_unique'] ?? '') ?> &bull; Syndic : <?= htmlspecialchars($residence['nom_syndic'] ?? '') ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Côté Droit : Trésorerie, Sélecteur Exercice, Boutons d'Action Rapide, Thème -->
            <div class="flex items-center gap-2 sm:gap-3">

                <!-- Co-Branding Bayan Gestion (Éditeur de la solution) -->
                <div class="hidden xl:flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/80 dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] text-[11px]" title="Solution éditée par Bayan Gestion">
                    <span class="text-slate-400 dark:text-slate-400">Créé par</span>
                    <?= getBayanLogoSvg(16, 'auto', false) ?>
                </div>

                <!-- Pill Trésorerie en direct calculée sur l'exercice -->
                <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] border border-slate-200 dark:border-[#3D154F] text-xs">
                    <span class="text-[#D91C6E] dark:text-[#F26968]" aria-hidden="true">💰</span>
                    <span class="text-slate-500 dark:text-slate-400">Trésorerie :</span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= formatMAD((float) $cockpit['tresorerieDisponible']) ?></span>
                </div>

                <!-- Sélecteur d'Exercice Comptable -->
                <form method="GET" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-[#250832] border border-slate-200 dark:border-[#3D154F] text-xs">
                    <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($currentPage) ?>">
                    <span aria-hidden="true">📅</span>
                    <select
                        name="exercice"
                        onchange="this.form.submit()"
                        class="bg-transparent text-xs font-bold text-slate-900 dark:text-white border-none focus:outline-none cursor-pointer"
                    >
                        <?php foreach ([2023, 2024, 2025, 2026] as $y) { ?>
                            <option value="<?= $y ?>" <?= ($selectedExercice ?? 2025) == $y ? 'selected' : '' ?> class="bg-white dark:bg-[#1E0427] text-slate-900 dark:text-white">
                                <?= $y ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>

                <!-- Boutons d'Action Rapide réservés au rôle Syndic -->
                <?php if ($user['role'] === 'syndic') { ?>
                    <?php if (! $isReadOnly) { ?>
                        <!-- Déclenchement de la modale d'encaissement rapide -->
                        <button
                            onclick="document.getElementById('modal-paiement')?.classList.remove('hidden')"
                            class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md"
                        >
                            <span>➕ Paiement</span>
                        </button>

                        <!-- Déclenchement de la modale de dépense rapide -->
                        <button
                            onclick="document.getElementById('modal-depense')?.classList.remove('hidden')"
                            class="hidden sm:flex px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#250832] dark:hover:bg-[#340c44] border border-slate-200 dark:border-[#3D154F] text-slate-700 dark:text-slate-200 text-xs font-semibold transition items-center gap-1.5"
                        >
                            <span>🧾 Dépense</span>
                        </button>
                    <?php } else { ?>
                        <!-- Badge de blocage en mode lecture seule -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold shadow-sm" title="Toutes les écritures sont verrouillées">
                            <span aria-hidden="true">🔒</span>
                            <span>Mode Lecture Seule</span>
                        </span>
                    <?php } ?>
                <?php } ?>

                <!-- Bascule Dark / Light Interactive -->
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
        <!-- CONTENEUR PRINCIPAL DE LA PAGE                                    -->
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
                                <h3 class="text-sm font-bold tracking-tight text-rose-700 dark:text-rose-300">
                                    MODE LECTURE SEULE ACTIF (CONSULTATION UNIQUEMENT)
                                </h3>
                                <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider rounded-md bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30">
                                    Écritures & Ajouts Verrouillés
                                </span>
                            </div>
                            <p class="text-xs mt-1.5 text-slate-700 dark:text-slate-300 leading-relaxed">
                                Cette copropriété est actuellement verrouillée en <strong>consultation seule</strong> (défaut de règlement de l'abonnement ou période de grâce expirée).
                                Toutes les actions de création et d'édition (nouveau copropriétaire, nouvelle dépense, nouvel encaissement, signalement d'incident, nouvelle assemblée générale, modification des lots et paramètres de la copropriété) sont <strong>strictement désactivées</strong> pour tous les utilisateurs (Syndic Administrateur et Résidents).
                            </p>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if (($_GET['error'] ?? '') === 'read_only_mode') { ?>
                <!-- Toast de Rejet d'Action Forcée -->
                <div class="mb-6 p-4 rounded-2xl bg-rose-500/20 border-2 border-rose-600 text-rose-900 dark:text-rose-200 text-xs flex items-center justify-between shadow-lg">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl" aria-hidden="true">⛔</span>
                        <div>
                            <strong class="font-bold block text-sm">Action bloquée : Mode Lecture Seule Actif</strong>
                            <span>Toutes les créations et modifications sont verrouillées pour cette copropriété. Contactez l'administrateur de la plateforme pour régulariser la situation.</span>
                        </div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:text-rose-800 font-bold text-lg px-2 cursor-pointer">&times;</button>
                </div>
            <?php } ?>

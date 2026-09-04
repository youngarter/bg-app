<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - CONSOLE MASTER : EN-TÊTE GLOBAL DE SUPERVISION MULTI-TENANTS
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE DU MODULE ET SÉCURITÉ SUPER-ADMIN :
 * ----------------------------------------------------------------------------
 * Ce fichier structure la barre de contrôle supérieure de la console centrale
 * de supervision de l'éditeur Bayan Gestion.
 *
 * Traitements exécutés :
 * 1. Vérification de l'authentification de l'administrateur système via getCurrentSuperAdmin().
 * 2. Configuration du thème graphique d'administration avec la palette officielle Bayan.
 * 3. Affichage des raccourcis de maintenance globale :
 *    - Déclenchement de la sauvegarde ZIP chiffrée de toutes les bases tenants.
 *    - Modale de provisionnement immédiat d'une nouvelle copropriété cliente.
 *    - Indicateur de santé système (PHP 8.2 & SQLite PDO multi-tenants).
 *    - Gestionnaire de bascule d'ambiance visuelle (Deep Plum / Primrose).
 */

declare(strict_types=1);

require_once __DIR__.'/brand.php';

/**
 * Super-administrateur actuellement authentifié sur la console centrale.
 *
 * @var array<string, mixed>|null $currentAdmin
 */
$currentAdmin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayan Gestion - Console de Supervision Master</title>

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
         * Synchronisation visuelle des icônes d'ambiance
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
<body class="min-h-screen bg-[#FDF8F5] dark:bg-[#14021C] text-[#1E0427] dark:text-[#FAF4F8] flex flex-col font-sans transition-colors duration-200 selection:bg-[#D91C6E] selection:text-white">

    <?php if ($currentAdmin) { ?>
    <!-- ===================================================================== -->
    <!-- BANDEAU SUPÉRIEUR MASTER : SUPERVISION CENTRALE MULTI-TENANTS         -->
    <!-- ===================================================================== -->
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-[#1E0427]/95 backdrop-blur-xl border-b border-[#F0E4DC] dark:border-[#3D154F] px-6 py-3 flex items-center justify-between transition-colors">

        <!-- Logo & Titre Bayan Gestion -->
        <div class="flex items-center gap-4">
            <?= getBayanLogoSvg(32, 'auto', true) ?>
            <div class="hidden md:block pl-3 border-l border-[#F0E4DC] dark:border-[#3D154F]">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold tracking-tight text-slate-800 dark:text-white uppercase">Console Master</span>
                    <span class="px-2 py-0.5 rounded-full bg-[#D91C6E]/10 border border-[#D91C6E]/20 text-[10px] font-mono text-[#D91C6E] dark:text-[#F26968] font-bold">
                        Super Admin : <?= htmlspecialchars((string) $currentAdmin['nom']) ?>
                    </span>
                </div>
                <p class="text-[10.5px] text-slate-500 dark:text-slate-400">Supervision Multi-Tenants &bull; Partitionnement SQLite ACID</p>
            </div>
        </div>

        <!-- Outils d'administration système et actions rapides -->
        <div class="flex items-center gap-2.5">
            <!-- Badge technique de la pile d'exécution -->
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-[#250832] border border-slate-200 dark:border-[#3D154F] text-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-slate-600 dark:text-slate-300 font-mono text-[11px]">PHP 8.2 &bull; SQLite PDO</span>
            </div>

            <!-- Bascule Dark / Light Interactive -->
            <button
                onclick="toggleDarkMode()"
                class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#250832] dark:hover:bg-[#340c44] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#3D154F] transition"
                title="Basculer Mode Clair / Sombre"
            >
                <span class="icon-sun text-amber-500 font-bold" aria-hidden="true">☀️</span>
                <span class="icon-moon text-[#D91C6E] font-bold" aria-hidden="true">🌙</span>
            </button>

            <!-- Déclenchement de la sauvegarde d'archive ZIP de toutes les bases -->
            <button
                onclick="document.getElementById('modal-backup').classList.remove('hidden')"
                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#250832] dark:hover:bg-[#340c44] border border-slate-200 dark:border-[#3D154F] text-slate-700 dark:text-slate-300 text-xs font-semibold transition flex items-center gap-1.5 shadow-sm"
                title="Sauvegarde globale des bases de données SQLite"
            >
                <span class="text-amber-500" aria-hidden="true">🔒</span>
                <span class="hidden md:inline">Sauvegarde ZIP</span>
            </button>

            <!-- Déclenchement de la modale de provisionnement de nouvelle copropriété -->
            <button
                onclick="document.getElementById('modal-provision').classList.remove('hidden')"
                class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 transition flex items-center gap-1.5 shadow-md"
            >
                <span>➕ Provisionner Résidence</span>
            </button>

            <!-- Déconnexion sécurisée de la session Super Admin -->
            <a
                href="logout.php"
                class="p-2 rounded-xl bg-slate-100 hover:bg-red-100 dark:bg-[#250832] dark:hover:bg-red-950/40 text-slate-600 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 border border-slate-200 dark:border-[#3D154F] transition"
                title="Déconnexion du Super Admin"
            >
                🚪
            </a>
        </div>
    </header>
    <?php } ?>

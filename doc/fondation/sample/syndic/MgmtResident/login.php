<?php
/**
 * ==============================================================================
 * SyndicPro Maroc — Portail de Connexion Espace Copropriétaires (MgmtResident)
 * ==============================================================================
 * Ce script gère l'accès privatif des résidents et copropriétaires à leur portail :
 *
 * Logique Métier & Déroulement :
 * 1. Résolution du Tenant : Récupère le GUID de la copropriété et la fiche d'immeuble.
 * 2. Contrôle de Session Active :
 *    - Si déjà connecté comme résident, redirection directe vers son tableau de bord.
 *    - Si connecté avec un profil syndic, réorientation vers le cockpit de gestion.
 * 3. Traitement POST :
 *    - Validation des identifiants via loginResidentUser().
 *    - Prise en charge des formats conviviaux universels (ex: mehdi.elamrani@atlas, mehdi@atlas).
 *    - Redirection vers le cockpit individuel en cas de succès, message d'alerte sinon.
 * 4. Présentation Graphique :
 *    - Thématique visuelle Bayan Gestion avec affichage de l'armoirie / logo de la résidence.
 *    - Assistance à la saisie et bouton de démonstration rapide.
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/resident_auth.php';
require_once dirname(__DIR__).'/MgmtResidence/includes/tenant_db.php';
require_once dirname(__DIR__).'/MgmtResidence/includes/brand.php';

// Étape 1 : Résolution de la copropriété et du logo
$guid = TenantDB::resolveGuid();
$residence = TenantDB::getResidence();
$customLogoUrl = resolveResidenceLogo($residence['logo_url'] ?? null);

// Étape 2 : Contrôle de présence d'une session active
$user = getCurrentResidentUser();
if ($user) {
    if (($user['role'] ?? '') === 'syndic') {
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
        exit;
    }
    header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php');
    exit;
}

// Variable d'alerte pour affichage des erreurs
$error = '';

// Étape 3 : Traitement de la tentative de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'identifiant et du mot de passe
    $identifier = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (loginResidentUser($identifier, $password)) {
        $logged = getCurrentResidentUser();
        // Aiguillage si le compte connecté est un administrateur syndic
        if ($logged && ($logged['role'] ?? '') === 'syndic') {
            header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
            exit;
        }
        header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects ou compte copropriétaire introuvable pour cette copropriété.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Résident - <?= htmlspecialchars($residence['nom'] ?? 'Copropriété') ?> - Bayan Gestion</title>

    <!-- Favicon Officiel Bayan Gestion -->
    <link rel="icon" type="image/svg+xml" href="/Syndic/assets/img/bayan_icon.svg">
    <link rel="alternate icon" type="image/png" sizes="32x32" href="/Syndic/assets/img/bayan_icon_32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/Syndic/assets/img/bayan_icon_apple.png">
    <link rel="shortcut icon" href="/Syndic/favicon.ico">

    <!-- Police Poppins Officielle Bayan Gestion -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN avec Palette Bayan Gestion -->
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
                            dark: '#14021C',   // Deepest Dark Background
                            surface: '#22082E',// Dark Card Surface
                            border: '#3D154F'  // Plum Border
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
        html.light { background-color: #FDF8F5; color: #1E0427; font-family: 'Poppins', sans-serif; }
        html.dark { background-color: #14021C; color: #FAF4F8; font-family: 'Poppins', sans-serif; }
    </style>
    <script>
        const savedTheme = localStorage.getItem('syndic_color_mode') || 'dark';
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }

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
<body class="min-h-screen bg-[#FDF8F5] dark:bg-[#14021C] text-[#1E0427] dark:text-[#FAF4F8] flex flex-col justify-between p-6 font-sans transition-colors duration-200 selection:bg-[#D91C6E] selection:text-white">

    <!-- Top Navbar avec Logo Résidence & Co-Marque Bayan Gestion -->
    <header class="max-w-5xl w-full mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <?= renderResidenceLogoBadge($residence, 36, false) ?>
            <div>
                <h1 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight"><?= htmlspecialchars($residence['nom'] ?? 'Copropriété') ?></h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400"><?= htmlspecialchars($residence['ville'] ?? 'Maroc') ?> &bull; Portail Copropriétaires</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white/80 dark:bg-[#1E0427]/80 border border-[#F0E4DC] dark:border-[#3D154F] text-xs">
                <span class="text-slate-400">Plateforme créée par</span>
                <?= getBayanLogoSvg(17, 'auto', false) ?>
            </div>

            <button
                onclick="toggleDarkMode()"
                class="p-2 rounded-xl bg-white dark:bg-[#1E0427] hover:bg-slate-100 dark:hover:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] text-slate-700 dark:text-slate-300 transition"
                title="Basculer Mode Clair / Sombre"
            >
                <span class="icon-sun text-amber-500 font-bold">☀️</span>
                <span class="icon-moon text-[#D91C6E] font-bold">🌙</span>
            </button>
        </div>
    </header>

    <!-- Main Login Card -->
    <main class="max-w-md w-full mx-auto my-auto space-y-5">
        <div class="p-8 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-2xl space-y-6 transition-colors">
            <!-- Header de Connexion Résident -->
            <div class="text-center space-y-2">
                <div class="flex justify-center">
                    <?= renderResidenceLogoMain($residence, 64) ?>
                </div>

                <div>
                    <span class="inline-block px-3 py-0.5 rounded-full text-[11px] font-bold bg-[#D91C6E]/15 text-[#D91C6E] dark:text-[#F26968] border border-[#D91C6E]/20 mb-1">
                        Espace Privé Copropriétaire
                    </span>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight"><?= htmlspecialchars($residence['nom']) ?></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Consultez vos cotisations, quittances et signalements</p>
                </div>
            </div>

            <?php if ($error) { ?>
                <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php } ?>

            <form method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Identifiant Résident (ex: mehdi@<?= TenantDB::getResidenceTag() ?>)
                    </label>
                    <input
                        type="text"
                        name="email"
                        id="input-identifiant"
                        required
                        value="mehdi@<?= TenantDB::getResidenceTag() ?>"
                        placeholder="nom@<?= TenantDB::getResidenceTag() ?>"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none font-mono"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mot de Passe</label>
                    <input
                        type="password"
                        name="password"
                        id="input-password"
                        required
                        value="resident2026"
                        placeholder="••••••••••••"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-lg mt-1"
                >
                    <span>Accéder à Mon Espace Résident</span>
                    <span>&rarr;</span>
                </button>
            </form>

            <!-- Raccourci vers Espace Syndic -->
            <div class="pt-3 border-t border-[#F0E4DC] dark:border-[#3D154F] flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Vous êtes membre du Syndic ?</span>
                <a
                    href="/Syndic/MgmtResidence/<?= urlencode($guid) ?>/login.php?role=syndic"
                    class="font-bold text-[#D91C6E] dark:text-[#F26968] hover:underline"
                >
                    Accès Syndic &rarr;
                </a>
            </div>
        </div>
    </main>

    <!-- Footer Législatif avec Signature Créateur Bayan Gestion -->
    <footer class="max-w-5xl w-full mx-auto text-center text-slate-400 text-[11px] space-y-1">
        <div class="flex items-center justify-center gap-2 font-medium">
            <span>Créé & Développé par</span>
            <span class="font-bold text-[#D91C6E] dark:text-[#F26968]">Bayan Gestion</span>
            <span>&bull;</span>
            <span><?= htmlspecialchars($residence['nom']) ?></span>
        </div>
        <div>Royaume du Maroc &bull; Dahir n° 1-02-298 (Loi 18-00 relative à la copropriété des immeubles bâtis)</div>
    </footer>
</body>
</html>

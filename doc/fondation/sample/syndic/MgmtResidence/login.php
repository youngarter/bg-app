<?php
/**
 * ==============================================================================
 * SyndicPro Maroc — Écran de Connexion & Authentification (Cockpit Syndic)
 * ==============================================================================
 * Ce script gère le point d'entrée sécurisé pour les administrateurs et membres délégués :
 *
 * Logique Métier & Déroulement :
 * 1. Résolution de la Copropriété : Détermine le GUID actif et charge la fiche de la résidence.
 * 2. Vérification de Session Active :
 *    - Si l'utilisateur est déjà connecté en tant que Syndic ou Délégué, redirection vers le tableau de bord.
 *    - Si l'utilisateur est un simple résident sans délégation, réorientation vers l'Espace Résident.
 * 3. Traitement POST :
 *    - Interroge loginUser() pour valider l'identifiant (email, user@tag, nom) et le mot de passe.
 *    - En cas de succès, analyse le profil pour appliquer l'aiguillage approprié.
 *    - En cas d'échec, affiche une alerte contextuelle sans divulguer de détails sensibles.
 * 4. Présentation Visuelle :
 *    - Commutateur de profil (Syndic vs Résident/Délégué) avec pré-remplissage des accès de test.
 *    - Charte graphique officielle Bayan Gestion (Nuances Nuit Profonde et Magenta Signature).
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/tenant_auth.php';
require_once __DIR__.'/includes/tenant_db.php';
require_once __DIR__.'/includes/brand.php';

// Étape 1 : Résolution de la copropriété cible
$guid = TenantDB::resolveGuid();
$residence = TenantDB::getResidence();
$customLogoUrl = resolveResidenceLogo($residence['logo_url'] ?? null);

// Étape 2 : Contrôle de présence d'une session active pour éviter une double authentification
$user = getCurrentUser();
if ($user) {
    // Si simple résident sans mandat de délégation, renvoyer vers son espace personnel
    if (($user['role'] ?? '') === 'resident' && empty($user['delegate'])) {
        header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php');
        exit;
    }
    // Si syndic ou membre délégué, ouvrir directement le tableau de bord de gestion
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
    exit;
}

// Variables de gestion de l'état du formulaire
$error = '';
$role = $_GET['role'] ?? 'syndic';

// Étape 3 : Traitement de la soumission du formulaire d'authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données saisies
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $postRole = $_POST['role'] ?? 'syndic';

    // Tentative de connexion via la couche d'authentification
    if (loginUser($email, $password)) {
        $loggedUser = getCurrentUser();
        // Aiguillage selon le profil réel obtenu
        if ($loggedUser && ($loggedUser['role'] ?? '') === 'resident' && empty($loggedUser['delegate'])) {
            header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php');
            exit;
        }
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects pour cette copropriété.';
        $role = $postRole;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= htmlspecialchars($residence['nom'] ?? 'Copropriété') ?> - Bayan Gestion</title>

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
                <p class="text-[11px] text-slate-500 dark:text-slate-400"><?= htmlspecialchars($residence['ville'] ?? 'Maroc') ?> &bull; <?= htmlspecialchars($residence['code_unique'] ?? '') ?></p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Co-Branding Bayan Gestion -->
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
            <!-- Header de Connexion -->
            <div class="text-center space-y-2">
                <div class="flex justify-center">
                    <?= renderResidenceLogoMain($residence, 64) ?>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Accès <?= htmlspecialchars($residence['nom']) ?></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Authentification Syndic & Espace Privé Copropriétaire</p>
                </div>
            </div>

            <!-- Role Tabs -->
            <div class="grid grid-cols-2 gap-1.5 p-1 bg-[#FDF8F5] dark:bg-[#14021C] rounded-2xl border border-[#F0E4DC] dark:border-[#3D154F]">
                <button
                    type="button"
                    onclick="selectRole('syndic')"
                    id="btn-role-syndic"
                    class="py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 <?= $role === 'syndic' ? 'bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white shadow-md' : 'text-slate-500 dark:text-slate-400' ?>"
                >
                    <span>⚡ Syndic Admin</span>
                </button>
                <button
                    type="button"
                    onclick="selectRole('resident')"
                    id="btn-role-resident"
                    class="py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 <?= $role === 'resident' ? 'bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white shadow-md' : 'text-slate-500 dark:text-slate-400' ?>"
                >
                    <span>👤 Copropriétaire</span>
                </button>
            </div>

            <?php if ($error) { ?>
                <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php } ?>

            <form method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="role" id="form-role" value="<?= htmlspecialchars($role) ?>">
                
                <div>
                    <label id="label-identifiant" class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        <?= $role === 'syndic' ? 'Email Administrateur' : 'Identifiant Résident (ex: mehdi@'.TenantDB::getResidenceTag().')' ?>
                    </label>
                    <input
                        type="text"
                        name="email"
                        id="input-email"
                        required
                        value="<?= htmlspecialchars($role === 'syndic' ? $residence['email_syndic'] : ('mehdi@'.TenantDB::getResidenceTag())) ?>"
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
                        value="<?= $role === 'syndic' ? 'syndic2026' : 'resident2026' ?>"
                        placeholder="••••••••••••"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-lg mt-1"
                >
                    <span id="submit-text">Accéder à l'Espace <?= $role === 'syndic' ? 'Syndic' : 'Résident' ?></span>
                    <span>→</span>
                </button>
            </form>

            <!-- Format d'identifiant convivial -->
            <div class="p-2.5 rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] text-[11px] text-slate-500 dark:text-slate-400 text-center">
                <span>Format convivial résidents : <strong class="font-mono text-emerald-600 dark:text-emerald-400">user@<?= TenantDB::getResidenceTag() ?></strong></span>
            </div>

            <!-- Pré-remplissage Rapide Démo -->
            <div class="pt-2 border-t border-[#F0E4DC] dark:border-[#3D154F] text-center">
                <button
                    type="button"
                    onclick="fillDemo()"
                    class="text-[11px] text-slate-500 dark:text-slate-400 hover:text-[#D91C6E] dark:hover:text-[#F26968] transition underline"
                >
                    Pré-remplir les identifiants de test
                </button>
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

    <script>
        const resTag = "<?= TenantDB::getResidenceTag() ?>";

        function selectRole(role) {
            document.getElementById('form-role').value = role;
            const btnSyndic = document.getElementById('btn-role-syndic');
            const btnResident = document.getElementById('btn-role-resident');
            const labelIdent = document.getElementById('label-identifiant');
            const submitText = document.getElementById('submit-text');

            if (role === 'syndic') {
                btnSyndic.className = "py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white shadow-md";
                btnResident.className = "py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-slate-500 dark:text-slate-400";
                labelIdent.textContent = "Email Administrateur";
                submitText.textContent = "Accéder à l'Espace Syndic";
                document.getElementById('input-email').value = "<?= htmlspecialchars($residence['email_syndic']) ?>";
                document.getElementById('input-password').value = "syndic2026";
            } else {
                btnResident.className = "py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white shadow-md";
                btnSyndic.className = "py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-slate-500 dark:text-slate-400";
                labelIdent.textContent = "Identifiant Résident (ex: mehdi@" + resTag + ")";
                submitText.textContent = "Accéder à l'Espace Résident";
                document.getElementById('input-email').value = "mehdi@" + resTag;
                document.getElementById('input-password').value = "resident2026";
            }
        }

        function fillDemo() {
            const role = document.getElementById('form-role').value;
            selectRole(role);
        }
    </script>
</body>
</html>

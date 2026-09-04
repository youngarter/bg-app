<?php
/**
 * ==============================================================================
 * SyndicPro Maroc — Portail de Connexion Super-Administrateur (Console Master)
 * ==============================================================================
 * Ce script gère l'authentification des équipes de supervision de l'éditeur Bayan Gestion :
 *
 * Traitement & Logique :
 * 1. Vérifie si une session Super-Admin est déjà ouverte (redirection vers index.php).
 * 2. Traite la soumission POST : assainit l'adresse email et valide le mot de passe via MasterDB.
 * 3. En cas de succès : stocke le profil administrateur en session et redirige vers le dashboard.
 * 4. En cas d'échec : consigne le refus et affiche un message d'alerte explicite.
 * 5. Présente une interface soignée aux couleurs exécutives Bayan Gestion (#1E0427, #D91C6E).
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/master_db.php';
require_once __DIR__.'/includes/brand.php';

// Étape 1 : Redirection immédiate si l'administrateur est déjà authentifié
if (! empty($_SESSION['super_admin'])) {
    header('Location: index.php');
    exit;
}

// Variable d'affichage des messages d'erreur
$error = '';

// Étape 2 : Traitement de la soumission du formulaire d'authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données soumises
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Tentative d'authentification via le registre maître SQLite
    $admin = MasterDB::authenticateSuperAdmin($email, $password);
    if ($admin) {
        // Enregistrement des données de l'administrateur en session sécurisée
        $_SESSION['super_admin'] = $admin;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants Super Administrateur invalides ou accès non autorisé.';
    }
}

require_once __DIR__.'/includes/header.php';
?>

<div class="min-h-screen flex flex-col justify-between p-6">
    <div class="max-w-md w-full mx-auto my-auto space-y-6">
        <div class="p-8 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-2xl space-y-6 transition-colors">
            <!-- Brand Logo Bayan Gestion -->
            <div class="text-center space-y-3">
                <div class="flex justify-center">
                    <?= getBayanLogoSvg(42, 'auto', true) ?>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight uppercase">Console Master de Supervision</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Accès centralisé à la gestion des copropriétés & licences</p>
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
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Super Admin</label>
                    <input
                        type="email"
                        name="email"
                        id="login-email"
                        required
                        value="admin@syndicpro.ma"
                        placeholder="admin@syndicpro.ma"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mot de Passe</label>
                    <input
                        type="password"
                        name="password"
                        id="login-pass"
                        required
                        value="master2026"
                        placeholder="••••••••••••"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:border-[#D91C6E] focus:outline-none"
                    />
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-lg mt-2"
                >
                    <span>Ouvrir la Console Master</span>
                    <span>→</span>
                </button>
            </form>

            <div class="pt-3 border-t border-[#F0E4DC] dark:border-[#3D154F] text-center">
                <button
                    type="button"
                    onclick="document.getElementById('login-email').value='admin@syndicpro.ma'; document.getElementById('login-pass').value='master2026';"
                    class="text-[11px] text-slate-500 dark:text-slate-400 hover:text-[#D91C6E] dark:hover:text-[#F26968] transition underline"
                >
                    Pré-remplir les accès Master par défaut
                </button>
            </div>
        </div>
    </div>

    <!-- Footer Bayan Gestion -->
    <footer class="max-w-md w-full mx-auto text-center text-slate-400 text-[11px] space-y-1">
        <div class="font-semibold text-slate-600 dark:text-slate-400">Bayan Gestion &bull; One Brand. Multiple Expertise. Unlimited Trust.</div>
        <div>Supervision Master &bull; Architecture Multi-Tenant Partitionnée ACID</div>
    </footer>
</div>
</body>
</html>

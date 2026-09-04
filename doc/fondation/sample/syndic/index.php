<?php
/**
 * ==============================================================================
 * SyndicPro Maroc — Portail d'Aiguillage Central & Sélecteur Multi-Tenant
 * ==============================================================================
 * Point d'entrée principal de la plateforme web (/Syndic/) :
 *
 * Architecture & Rôle Tri-Tier :
 * 1. Console Master (MgmtConsole/) : Supervision centrale et gestion des licences.
 * 2. Espace Syndic (MgmtResidence/) : Cockpit de gestion pour les syndics et délégués.
 * 3. Espace Copropriétaires (MgmtResident/) : Portail privatif pour les résidents.
 *
 * Traitement & Logique :
 * - Interroge la base de données maître (data/master.sqlite) pour extraire la liste
 *   des copropriétés actives et leurs identifiants GUID.
 * - Propose des raccourcis de redirection vers chaque application et copropriété.
 * - Affiche l'identité visuelle officielle Bayan Gestion conforme à la Loi 18-00.
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/MgmtResidence/includes/brand.php';

// Étape 1 : Chargement de la liste des copropriétés depuis le registre maître SQLite
$masterDbPath = __DIR__.'/data/master.sqlite';
$tenants = [];
if (file_exists($masterDbPath)) {
    try {
        $pdo = new PDO('sqlite:'.$masterDbPath);
        $tenants = $pdo->query('SELECT id, nom, ville, code_unique FROM tenants ORDER BY nom ASC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // En cas d'erreur de lecture, un tableau vide est utilisé
        $tenants = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayan Gestion - Portail Multi-Tenant Copropriétés</title>

    <!-- Favicon Officiel Bayan Gestion -->
    <link rel="icon" type="image/svg+xml" href="/Syndic/assets/img/bayan_icon.svg">
    <link rel="alternate icon" type="image/png" sizes="32x32" href="/Syndic/assets/img/bayan_icon_32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/Syndic/assets/img/bayan_icon_apple.png">
    <link rel="shortcut icon" href="/Syndic/favicon.ico">

    <!-- Police Poppins Officielle Bayan Gestion -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bayan: {
                            500: '#D91C6E',
                            orange: '#F27835',
                            dark: '#14021C',
                            surface: '#1E0427',
                            border: '#3D154F'
                        }
                    },
                    fontFamily: {
                        sans: ['Poppins', 'Segoe UI', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif']
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen bg-[#14021C] text-slate-100 flex flex-col justify-between p-6 font-['Poppins'] selection:bg-[#D91C6E] selection:text-white">
    <div class="max-w-4xl w-full mx-auto my-auto space-y-8">
        <!-- Logo & Brand Header Bayan Gestion -->
        <div class="text-center space-y-3">
            <div class="flex justify-center">
                <?= getBayanLogoSvg(48, 'dark', true) ?>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#D91C6E]/10 border border-[#D91C6E]/30 text-[#F26968] text-xs font-mono">
                <span>المملكة المغربية &bull; Dahir n° 1-02-298 &bull; Loi 18-00</span>
            </div>
            <p class="text-slate-400 text-xs max-w-xl mx-auto leading-relaxed">
                Plateforme intégrée de gestion de copropriété développée par <strong class="text-white">Bayan Gestion</strong>. Architecture partitionnée haute sécurité (bases SQLite isolées par copropriété sous GUID RFC 4122).
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Console Super Admin Bayan Gestion -->
            <div class="p-6 rounded-3xl bg-[#1E0427] border border-[#3D154F] shadow-2xl space-y-4 hover:border-[#D91C6E] transition flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <?= getBayanIconSvg(32) ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#D91C6E]/20 text-[#F26968] border border-[#D91C6E]/30 uppercase">
                            Master Super Admin
                        </span>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white">Console Master</h2>
                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                            Supervision centrale, provisionnement multi-tenant, gestion des licences et lecture seule.
                        </p>
                    </div>
                </div>
                <div class="pt-2">
                    <a
                        href="MgmtConsole/index.php"
                        class="block w-full py-2 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs text-center transition shadow-lg"
                    >
                        Accéder &rarr;
                    </a>
                </div>
            </div>

            <!-- 2. Application Syndic Admin (MgmtResidence) -->
            <div class="p-6 rounded-3xl bg-[#1E0427] border border-[#3D154F] shadow-2xl space-y-4 hover:border-[#F27835] transition flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-base shadow-md">
                            ⚡
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#F27835]/20 text-[#F27835] border border-[#F27835]/30 uppercase">
                            Syndic Admin
                        </span>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white">Espace Syndic</h2>
                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                            Cockpit financier, appels de fonds, quittances Loi 18-00, dépenses, AG et carnet d'entretien.
                        </p>
                    </div>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">
                        <?php foreach ($tenants as $t) { ?>
                            <a
                                href="MgmtResidence/<?= htmlspecialchars($t['id']) ?>/index.php"
                                class="flex items-center justify-between p-2 rounded-xl bg-[#14021C] hover:bg-[#250832] text-[11px] border border-[#3D154F] transition"
                            >
                                <span class="font-bold text-white truncate"><?= htmlspecialchars($t['nom']) ?></span>
                                <span class="text-[10px] text-[#F27835] font-mono shrink-0 ml-1">Syndic ↗</span>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- 3. Application Espace Résidents (MgmtResident) -->
            <div class="p-6 rounded-3xl bg-[#1E0427] border border-[#3D154F] shadow-2xl space-y-4 hover:border-emerald-500 transition flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center font-bold text-base shadow-md">
                            👤
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">
                            Copropriétaires
                        </span>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white">Espace Résidents</h2>
                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                            Cockpit personnel, téléchargement des quittances libératoires, signalement de pannes et PV d'AG.
                        </p>
                    </div>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">
                        <?php foreach ($tenants as $t) { ?>
                            <a
                                href="MgmtResident/<?= htmlspecialchars($t['id']) ?>/index.php"
                                class="flex items-center justify-between p-2 rounded-xl bg-[#14021C] hover:bg-[#250832] text-[11px] border border-[#3D154F] transition"
                            >
                                <span class="font-bold text-white truncate"><?= htmlspecialchars($t['nom']) ?></span>
                                <span class="text-[10px] text-emerald-400 font-mono shrink-0 ml-1">Résident ↗</span>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bayan Gestion -->
    <footer class="text-center text-xs text-slate-500 space-y-1">
        <div class="font-semibold text-slate-400">Bayan Gestion &bull; Excellence in every detail. Trust in every detail.</div>
        <div>Royaume du Maroc &bull; Dahir n° 1-02-298 (Loi 18-00) &bull; <?= date('Y') ?></div>
    </footer>
</body>
</html>

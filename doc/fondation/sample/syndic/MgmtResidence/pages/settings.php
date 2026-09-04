<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/settings.php
 * TYPE           : Vue Métier / Configuration Générale & Personnalisation
 * MODULE         : Identité Visuelle, Coordonnées Bancaires (RIB) & Mandat Syndic
 * CADRE JURIDIQUE: Loi n° 18-00 relative au statut de la copropriété
 *                  - Article 18 : Compte bancaire séparé obligatoire au nom du syndicat
 *                  - Immatriculation foncière (Titre Foncier Mère)
 *                  - Mentions obligatoires sur les quittances et notifications
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module permet de configurer l'ensemble des paramètres légaux, financiers
 * et graphiques de la copropriété.
 *
 * Paramètres et fonctionnalités :
 * 1. Personnalisation de Marque (Branding & Blasons) :
 *    - Téléversement d'un logo personnalisé (PNG, SVG, JPG, WEBP).
 *    - Sélection parmi 4 emblèmes prestigieux vectoriels locaux (Atlas Royal,
 *      Marina Luxury, Palmier d'Or, Modern Tower).
 *    - Co-marquage élégant préservant la mention de l'éditeur Bayan Gestion.
 * 2. URL Officielle Partitionnée (Format GUID) :
 *    - Génération et affichage de l'URL dédiée d'accès direct avec bouton de copie.
 * 3. Données Réglementaires & Bancaires (Loi 18-00) :
 *    - Nom officiel de la copropriété et Titre Foncier (TF) Mère.
 *    - Relevé d'Identité Bancaire (RIB 24 chiffres) du compte bancaire séparé.
 *    - Coordonnées officielles du Syndic en exercice (Nom, Téléphone, Email).
 * 4. Barrière de Sécurité :
 *    - Verrouillage total des formulaires si le tenant est placé en lecture seule.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS ET RÉSOLUTION DES DONNÉES DE LA COPROPRIÉTÉ
// ----------------------------------------------------------------------------
// brand.php : Fonctions graphiques et de résolution de logo personnalisé.
require_once dirname(__DIR__).'/includes/brand.php';

// Fiche signalétique complète de la résidence depuis la base SQLite dédiée.
$res = TenantDB::getResidence();

// Messages de confirmation ou d'erreur transmis via l'URL.
$msg = $_GET['saved'] ?? null;
$brandingMsg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

// Résolution de l'URL effective du logo de la copropriété.
$customLogoUrl = resolveResidenceLogo($res['logo_url'] ?? null);

// Contrôle de la licence commerciale du tenant (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6 max-w-4xl">
    <!-- En-tête de section avec indicateurs de statut -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Paramètres, Identité Visuelle & Mentions Légales</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Personnalisation du logo, coordonnées bancaires (RIB), mandat du syndic et mentions Loi 18-00</p>
        </div>

        <?php if ($msg) { ?>
            <span class="px-3 py-1 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-bold border border-emerald-500/20">
                ✅ Modifications enregistrées avec succès !
            </span>
        <?php } ?>

        <?php if ($brandingMsg === 'logo_saved') { ?>
            <span class="px-3 py-1 rounded-xl bg-gradient-to-r from-[#D91C6E]/10 to-[#F27835]/10 text-[#D91C6E] dark:text-[#F26968] text-xs font-bold border border-[#D91C6E]/20">
                ✨ Logo personnalisé enregistré avec succès !
            </span>
        <?php } elseif ($brandingMsg === 'logo_reset') { ?>
            <span class="px-3 py-1 rounded-xl bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-slate-300 text-xs font-bold">
                🔄 Logo réinitialisé au blason par défaut.
            </span>
        <?php } ?>

        <?php if ($error) { ?>
            <span class="px-3 py-1 rounded-xl bg-rose-500/10 text-rose-600 text-xs font-bold border border-rose-500/20">
                ⚠️ <?= htmlspecialchars($error) ?>
            </span>
        <?php } ?>
    </div>

    <!-- 1. BLOC PERSONNALISATION DE MARQUE & LOGO DE LA COPROPRIÉTÉ -->
    <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-5 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
            <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🎨</span>
                    <span>Personnalisation & Logo de la Copropriété / Cabinet Syndic</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Votre logo apparaîtra sur le bandeau supérieur, sur la page de connexion résidents, sur les quittances imprimables et sur les PV d'AG.
                </p>
            </div>
            <!-- Badge de co-marquage avec l'éditeur Bayan Gestion -->
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gradient-to-r from-[#D91C6E]/10 to-[#F27835]/10 border border-[#D91C6E]/20 text-[11px] self-start sm:self-auto">
                <span class="text-slate-500 dark:text-slate-400">Créé par</span>
                <?= getBayanLogoSvg(15, 'auto', false) ?>
            </div>
        </div>

        <!-- Aperçu Actuel du Logo ou de l'Emblème Actif -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
            <div class="md:col-span-1 text-center space-y-2">
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aperçu du Logo Actif</div>
                <div class="h-24 w-full max-w-[200px] mx-auto rounded-2xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] flex items-center justify-center p-3 shadow-inner">
                    <?php if ($customLogoUrl) { ?>
                        <img src="<?= htmlspecialchars($customLogoUrl) ?>" alt="Logo Copropriété" class="max-h-full max-w-full object-contain rounded-lg" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display:none;" class="flex flex-col items-center gap-1.5">
                            <?= getResidenceLogoPlaceholderSvg($res, 52) ?>
                            <span class="text-[9px] text-[#D91C6E] dark:text-[#F26968] font-bold uppercase tracking-wider">Icône par Défaut</span>
                        </div>
                    <?php } else { ?>
                        <div class="flex flex-col items-center gap-1.5">
                            <?= getResidenceLogoPlaceholderSvg($res, 52) ?>
                            <span class="text-[9px] text-[#D91C6E] dark:text-[#F26968] font-bold uppercase tracking-wider">Icône par Défaut</span>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="md:col-span-2 space-y-2 text-xs">
                <div class="font-bold text-slate-800 dark:text-white">Statut de la personnalisation :</div>
                <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                    <?php if ($customLogoUrl) { ?>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">✅ Logo personnalisé actif :</span> L'application est personnalisée avec les armoiries ou le logo de votre copropriété, tout en préservant le co-marquage avec le créateur <strong>Bayan Gestion</strong>.
                    <?php } else { ?>
                        <span class="text-amber-600 dark:text-amber-400 font-bold">ℹ️ Icône Officielle par Défaut :</span> L'insigne officiel de la copropriété (généré automatiquement à la création) est actuellement en service. Téléversez le logo de votre copropriété ou cabinet pour le remplacer, ou sélectionnez un emblème prédéfini.
                    <?php } ?>
                </p>

                <?php if ($customLogoUrl && ! $isReadOnly) { ?>
                    <!-- Formulaire de réinitialisation au blason par défaut -->
                    <form action="actions/save_branding.php" method="POST" class="inline-block pt-1">
                        <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
                        <input type="hidden" name="reset_logo" value="1">
                        <button type="submit" onclick="return confirm('Rétablir l\'icône par défaut de la copropriété ?')" class="px-3 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition">
                            🔄 Rétablir l'icône par défaut
                        </button>
                    </form>
                <?php } ?>
            </div>
        </div>

        <?php if ($isReadOnly) { ?>
            <!-- Alerte Verrouillage Personnalisation de Marque -->
            <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2.5">
                <span class="text-base">🔒</span>
                <span>Personnalisation de marque verrouillée : La copropriété est en mode lecture seule. Téléversement, modification et réinitialisation de logo sont désactivés.</span>
            </div>
        <?php } ?>

        <!-- Formulaire de Choix & Téléversement de Logo -->
        <form action="actions/save_branding.php" method="POST" enctype="multipart/form-data" class="space-y-4 pt-2">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Option A: Téléversement de fichier local -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2">
                    <label class="block font-bold text-xs text-slate-900 dark:text-white">
                        📁 Option A : Téléverser un fichier Logo
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Formats recommandés : PNG transparent, SVG, WEBP ou JPG (max 5 Mo).</p>
                    <input
                        type="file"
                        name="logo_file"
                        accept="image/png,image/jpeg,image/svg+xml,image/webp"
                        <?= $isReadOnly ? 'disabled' : '' ?>
                        class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#D91C6E]/10 file:text-[#D91C6E] hover:file:bg-[#D91C6E]/20 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>

                <!-- Option B: URL directe ou chemin d'accès -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2">
                    <label class="block font-bold text-xs text-slate-900 dark:text-white">
                        🔗 Option B : URL de l'image
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Indiquez l'URL ou chemin local de votre logo.</p>
                    <input
                        type="text"
                        name="logo_url"
                        value="<?= htmlspecialchars($res['logo_url'] ?? '') ?>"
                        placeholder="Ex: uploads/logos/mon_logo.png"
                        <?= $isReadOnly ? 'disabled' : '' ?>
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-xs font-mono focus:outline-none focus:border-[#D91C6E] disabled:opacity-50 disabled:cursor-not-allowed"
                    />
                </div>
            </div>

            <!-- Option C: Presets de Blasons Prestigieux Prêts à l'emploi (100% Locaux) -->
            <div class="p-4 rounded-2xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block font-bold text-xs text-slate-900 dark:text-white">
                        ✨ Option C : Blasons et Emblèmes Prédéfinis (Vectoriels Locaux)
                    </label>
                    <span class="text-[10px] text-slate-400"><?= $isReadOnly ? 'Désactivé en lecture seule' : 'Cliquez pour appliquer immédiatement' ?></span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                    <button
                        type="button"
                        <?= $isReadOnly ? 'disabled' : 'onclick="document.querySelector(\'input[name=logo_url]\').value=\'uploads/logos/presets/atlas_royal.svg\'; this.form.submit();"' ?>
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#D91C6E] text-center space-y-1 transition hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="text-xl">🏛️</div>
                        <div class="font-bold text-[11px]">Atlas Royal</div>
                    </button>

                    <button
                        type="button"
                        <?= $isReadOnly ? 'disabled' : 'onclick="document.querySelector(\'input[name=logo_url]\').value=\'uploads/logos/presets/marina_luxury.svg\'; this.form.submit();"' ?>
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#D91C6E] text-center space-y-1 transition hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="text-xl">🌊</div>
                        <div class="font-bold text-[11px]">Marina Luxury</div>
                    </button>

                    <button
                        type="button"
                        <?= $isReadOnly ? 'disabled' : 'onclick="document.querySelector(\'input[name=logo_url]\').value=\'uploads/logos/presets/palmier_dor.svg\'; this.form.submit();"' ?>
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#D91C6E] text-center space-y-1 transition hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="text-xl">🌴</div>
                        <div class="font-bold text-[11px]">Palmier d'Or</div>
                    </button>

                    <button
                        type="button"
                        <?= $isReadOnly ? 'disabled' : 'onclick="document.querySelector(\'input[name=logo_url]\').value=\'uploads/logos/presets/modern_tower.svg\'; this.form.submit();"' ?>
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#D91C6E] text-center space-y-1 transition hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="text-xl">🏢</div>
                        <div class="font-bold text-[11px]">Modern Tower</div>
                    </button>
                </div>
            </div>

            <!-- Validation du formulaire de marque -->
            <div class="flex items-center justify-end gap-2 pt-1">
                <?php if (! $isReadOnly) { ?>
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs transition shadow-md flex items-center gap-1.5"
                    >
                        <span>💾 Enregistrer le Logo de la Résidence</span>
                    </button>
                <?php } else { ?>
                    <span class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-zinc-800 text-slate-400 dark:text-slate-500 font-bold text-xs flex items-center gap-1.5 cursor-not-allowed">
                        <span>🔒</span>
                        <span>Enregistrement Logo Désactivé (Lecture Seule)</span>
                    </span>
                <?php } ?>
            </div>
        </form>
    </div>

    <!-- 2. BANNIÈRE URL DÉDIÉE DE CONNEXION (FORMAT GUID CLOISONNÉ) -->
    <div class="p-5 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-2.5 transition-colors">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <span>🌐</span>
                <span>URL Officielle d'Accès Dédiée (Syndic & Résidents) :</span>
            </span>
            <button
                type="button"
                onclick="navigator.clipboard.writeText('<?= 'http://'.($_SERVER['HTTP_HOST'] ?? 'localhost').'/Syndic/MgmtResidence/'.$guid.'/index.php' ?>'); alert('Lien copié dans le presse-papiers !');"
                class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
                title="Copier l'adresse web de connexion"
            >
                <span>📋 Copier le Lien</span>
            </button>
        </div>

        <div class="p-2.5 rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] text-xs font-mono text-[#D91C6E] dark:text-[#F26968] break-all select-all font-bold">
            <?= 'http://'.($_SERVER['HTTP_HOST'] ?? 'localhost').'/Syndic/MgmtResidence/'.$guid.'/index.php' ?>
        </div>

        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row sm:items-center justify-between gap-1 pt-1 border-t border-[#F0E4DC] dark:border-[#3D154F]">
            <span>Identifiant Partitionné (GUID) : <strong class="font-mono text-slate-800 dark:text-slate-200"><?= htmlspecialchars($guid) ?></strong></span>
            <span>Format Convivial Résidents : <strong class="font-mono text-emerald-600 dark:text-emerald-400">user@<?= TenantDB::getResidenceTag() ?></strong></span>
        </div>
    </div>

    <!-- 3. FORMULAIRE DES PARAMÈTRES RÉGLEMENTAIRES (LOI 18-00) -->
    <form action="actions/save_settings.php" method="POST" class="space-y-6">
        <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">

        <?php if ($isReadOnly) { ?>
            <!-- Alerte Verrouillage Fiche Immeuble -->
            <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2.5">
                <span class="text-base">🔒</span>
                <span>Fiche copropriété verrouillée : Les coordonnées, le RIB et les informations de mandat ne peuvent pas être modifiés en mode lecture seule.</span>
            </div>
        <?php } ?>

        <!-- Bloc 1 : Identification Cadastrale et Foncière -->
        <div class="p-5 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                🏢 Identification de la Copropriété
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom Officiel de la Copropriété *</label>
                    <input type="text" name="nom" required value="<?= htmlspecialchars($res['nom'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-bold disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Titre Foncier Mère</label>
                    <input type="text" name="titre_foncier_mere" value="<?= htmlspecialchars($res['titre_foncier_mere'] ?? '') ?>" placeholder="Ex: TF 18452/01 Casablanca" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Adresse Complète</label>
                    <input type="text" name="adresse" value="<?= htmlspecialchars($res['adresse'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ville</label>
                    <input type="text" name="ville" value="<?= htmlspecialchars($res['ville'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-bold disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Code Postal</label>
                    <input type="text" name="code_postal" value="<?= htmlspecialchars($res['code_postal'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
            </div>
        </div>

        <!-- Bloc 2 : Compte Bancaire Séparé Obligatoire (Article 18) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                🏦 Coordonnées Bancaires du Syndicat des Copropriétaires
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="md:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Relevé d'Identité Bancaire (RIB 24 Chiffres) *</label>
                    <input type="text" name="rib_bancaire" required value="<?= htmlspecialchars($res['rib_bancaire'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-mono font-bold text-[#D91C6E] dark:text-[#F26968] disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Établissement Bancaire</label>
                    <input type="text" name="banque" value="<?= htmlspecialchars($res['banque'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
            </div>
        </div>

        <!-- Bloc 3 : Mandat Officiel du Syndic en Exercice -->
        <div class="p-5 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                👤 Syndic en Exercice (Mandat Loi 18-00)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom & Prénom du Syndic *</label>
                    <input type="text" name="nom_syndic" required value="<?= htmlspecialchars($res['nom_syndic'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl font-bold disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Téléphone Portable</label>
                    <input type="text" name="telephone_syndic" value="<?= htmlspecialchars($res['telephone_syndic'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Officiel du Syndic</label>
                    <input type="email" name="email_syndic" required value="<?= htmlspecialchars($res['email_syndic'] ?? '') ?>" <?= $isReadOnly ? 'disabled' : '' ?> class="w-full p-2.5 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl disabled:opacity-60 disabled:cursor-not-allowed">
                </div>
            </div>
        </div>

        <!-- Bouton d'enregistrement des paramètres de l'immeuble -->
        <div class="flex justify-end">
            <?php if (! $isReadOnly) { ?>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs rounded-xl shadow-md transition">
                    Enregistrer les Paramètres
                </button>
            <?php } else { ?>
                <span class="px-6 py-2.5 bg-slate-200 dark:bg-zinc-800 text-slate-400 dark:text-slate-500 font-bold text-xs rounded-xl flex items-center gap-2 cursor-not-allowed">
                    <span>🔒</span>
                    <span>Modification Désactivée (Mode Lecture Seule)</span>
                </span>
            <?php } ?>
        </div>
    </form>
</div>

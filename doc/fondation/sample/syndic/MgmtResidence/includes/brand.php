<?php

/**
 * ============================================================================
 * SYNDIC CONNECT / BAYAN GESTION - CHARTE VISUELLE & COMPOSANTS GRAPHIQUES
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * DIRECTION ARTISTIQUE & SPÉCIFICATIONS CHROMATIQUES :
 * ----------------------------------------------------------------------------
 * Système graphique officiel de la plateforme Bayan Gestion :
 * - Deep Plum (Fond sombre noble)      : #1E0427 / #14021C
 * - Signature Magenta (Couleur Maîtresse) : #D91C6E (variantes #BF155E / #C71F67)
 * - Salmon (Accent lumineux)             : #F26968
 * - Warm Orange (Énergie et vitalité)   : #F27835
 * - Primrose (Fond clair chaleureux)     : #FDF8F5
 * - Typographie officielle              : Google Fonts 'Poppins' (SemiBold, Bold, ExtraBold)
 *
 * GESTION DES LOGOS & PLACEHOLDERS DÉDIÉS :
 * ----------------------------------------------------------------------------
 * 1. Logotype Bayan Gestion : Forme vectorielle origami ruban 'b' stylisé avec
 *    dégradé quadri-stops (#C71F67 -> #D91C6E -> #F26968 -> #F27835).
 * 2. Monogramme / Placeholder Résidence : Génération dynamique d'un blason
 *    squircle aux initiales de la copropriété avec étoile marocaine sommitale
 *    pour toute copropriété sans logo personnalisé téléversé.
 * 3. Robustesse Anti-Image Brisée : Fonction de fallback multi-niveaux (résolution
 *    locale sur disque, validation URL absolue, et écouteur client onerror inline).
 */

declare(strict_types=1);

// ============================================================================
// 1. GÉNÉRATEURS VECTORIELS (SVG) DE LA MARQUE MAÎTRESSE BAYAN GESTION
// ============================================================================

/**
 * Génère le balisage SVG du logotype officiel complet de Bayan Gestion.
 *
 * Produit l'assemblage de l'icône ruban origami et de la signature typographique
 * Poppins avec mention optionnelle du sous-titre de marque.
 *
 * @param  int  $height  Hauteur de rendu en pixels (défaut : 36px)
 * @param  string  $variant  Variante chromatique du texte ('auto'|'dark'|'light')
 * @param  bool  $withTagline  Affichage ou masquage de la baseline de marque
 * @return string Balisage HTML/SVG prêt pour injection directe
 */
function getBayanLogoSvg(int $height = 36, string $variant = 'auto', bool $withTagline = false): string
{
    // Sélection des classes utilitaires Tailwind selon la variante de contraste
    $textClass = ($variant === 'dark') ? 'text-white' : (($variant === 'light') ? 'text-[#1E0427]' : 'text-slate-900 dark:text-white');
    $subClass = ($variant === 'dark') ? 'text-slate-300' : (($variant === 'light') ? 'text-[#D91C6E]' : 'text-[#D91C6E] dark:text-[#F26968]');

    // Bloc optionnel de sous-titre officiel
    $taglineHtml = $withTagline ? "
        <div class=\"text-[9px] uppercase tracking-wider font-medium opacity-75 $subClass\">
            Syndic & Facility Management
        </div>" : '';

    // Assemblage du logo complet en flexbox avec icône ruban
    return '
    <div class="inline-flex items-center gap-2.5 select-none">
        '.getBayanIconSvg((int) ($height * 1.1))."
        <div class=\"flex flex-col justify-center leading-none\">
            <div class=\"font-['Poppins'] font-bold text-lg tracking-tight $textClass flex items-baseline gap-1\">
                <span>bayan</span>
                <span class=\"text-[11px] font-extrabold uppercase tracking-[0.25em] text-[#D91C6E] dark:text-[#F26968]\">GESTION</span>
            </div>
            $taglineHtml
        </div>
    </div>";
}

/**
 * Génère l'icône vectorielle ruban origami 'b' stylisée de Bayan Gestion.
 *
 * Utilise un dégradé linéaire diagonal multi-stops avec identifiant aléatoire
 * pour éviter tout conflit de masque dans le document DOM.
 *
 * @param  int  $size  Dimension en pixels (largeur et hauteur du viewBox carré)
 * @return string Balisage SVG complet
 */
function getBayanIconSvg(int $size = 32): string
{
    // Génération d'un sel cryptographique pour garantir l'unicité de l'ID du gradient
    $id = 'bayan_'.bin2hex(random_bytes(3));

    return "
    <svg viewBox=\"0 0 100 100\" width=\"{$size}\" height=\"{$size}\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\" class=\"shrink-0 filter drop-shadow-sm select-none\">
        <defs>
            <linearGradient id=\"{$id}\" x1=\"10%\" y1=\"90%\" x2=\"90%\" y2=\"10%\">
                <stop offset=\"0%\" stop-color=\"#C71F67\" />
                <stop offset=\"35%\" stop-color=\"#D91C6E\" />
                <stop offset=\"70%\" stop-color=\"#F26968\" />
                <stop offset=\"100%\" stop-color=\"#F27835\" />
            </linearGradient>
        </defs>
        <!-- Forme origami ruban 'b' stylisée de Bayan Gestion -->
        <path d=\"M26 18 C26 12.48 30.48 8 36 8 C41.52 8 46 12.48 46 18 L46 44 C53.5 38.2 62.8 35.2 72.5 36.8 C87.6 39.3 99.5 52.3 100 67.5 C100.5 83.2 88.2 96 72.5 96 C63.8 96 55.7 92.4 49.8 86.6 C46.8 83.7 44.5 80.2 42.8 76.3 L42.8 88 C42.8 92.4 39.2 96 34.8 96 L26 96 C20.5 96 16 91.5 16 86 L16 28 C16 22.5 20.5 18 26 18 Z M62 48 C51.5 48 43 56.5 43 67 C43 77.5 51.5 86 62 86 C72.5 86 81 77.5 81 67 C81 56.5 72.5 48 62 48 Z\" fill=\"url(#{$id})\" fill-rule=\"evenodd\" clip-rule=\"evenodd\" />
        <!-- Accent subtil pli ruban pour donner du relief -->
        <path d=\"M46 44 C42.5 49 42.8 65 42.8 76.3 C44.5 80.2 46.8 83.7 49.8 86.6 C55.7 92.4 63.8 96 72.5 96 C88.2 96 100.5 83.2 100 67.5 C99.5 52.3 87.6 39.3 72.5 36.8 C62.8 35.2 53.5 38.2 46 44 Z\" fill=\"url(#{$id})\" opacity=\"0.2\" />
    </svg>";
}

// ============================================================================
// 2. MONOGRAMMES ET LOGOS PLACEHOLDERS POUR LES RÉSIDENCES SANS LOGO
// ============================================================================

/**
 * Calcule intelligemment les initiales représentatives du nom de la résidence.
 *
 * Élimine les mots génériques ("Résidence", "Immeuble", "Tour", etc.) et les
 * mots de liaison ("de", "du", "les", "et") pour isoler les 2 lettres maîtresses.
 *
 * @param  string  $name  Dénomination complète de la résidence
 * @return string Monogramme sur 2 lettres majuscules (ex: "GW" pour Greenwood)
 */
function getResidenceInitials(string $name): string
{
    // Nettoyage des préfixes institutionnels courants
    $clean = preg_replace('/^(résidence|residence|immeuble|complexe|tour|domaine)\s+/i', '', trim($name));

    // Découpage par séparateurs de mots
    $words = preg_split('/[\s\-\'\’]+/', (string) $clean);

    // Mots vides de sens à ignorer dans la composition du monogramme
    $stopWords = ['de', 'du', 'des', 'le', 'la', 'les', 'l', 'd', 'et', '&', 'au', 'aux'];

    // Filtrage des mots significatifs
    $meaningful = array_values(array_filter((array) $words, fn ($w) => ! in_array(strtolower((string) $w), $stopWords, true) && mb_strlen((string) $w) > 0));

    // Composition du monogramme (2 premières lettres distinctes ou 2 premières du mot unique)
    if (count($meaningful) >= 2) {
        return strtoupper(mb_substr($meaningful[0], 0, 1).mb_substr($meaningful[1], 0, 1));
    } elseif (count($meaningful) === 1) {
        return strtoupper(mb_substr($meaningful[0], 0, 2));
    }

    // Repli par défaut si aucun nom intelligible n'est fourni
    return 'CO';
}

/**
 * Retourne le Logo Placeholder Vectoriel officiel dédié pour chaque copropriété.
 *
 * Construit un blason squircle avec silhouette d'immeuble filaire, étoile marocaine
 * et monogramme calculé aux couleurs de la marque.
 *
 * @param  array<string, mixed>  $residence  Enregistrement complet de la résidence
 * @param  int  $size  Taille en pixels
 * @return string Balisage SVG complet et stylisé
 */
function getResidenceLogoPlaceholderSvg(array $residence, int $size = 40): string
{
    $name = $residence['nom'] ?? 'Copropriété';
    $initials = getResidenceInitials((string) $name);
    $uid = bin2hex(random_bytes(3));

    return "
    <svg viewBox=\"0 0 100 100\" width=\"{$size}\" height=\"{$size}\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\" class=\"shrink-0 filter drop-shadow-sm select-none\">
        <defs>
            <linearGradient id=\"bg_{$uid}\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">
                <stop offset=\"0%\" stop-color=\"#1E0427\" />
                <stop offset=\"100%\" stop-color=\"#2B0837\" />
            </linearGradient>
            <linearGradient id=\"grad_{$uid}\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">
                <stop offset=\"0%\" stop-color=\"#D91C6E\" />
                <stop offset=\"50%\" stop-color=\"#F26968\" />
                <stop offset=\"100%\" stop-color=\"#F27835\" />
            </linearGradient>
            <linearGradient id=\"gold_{$uid}\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">
                <stop offset=\"0%\" stop-color=\"#F27835\" />
                <stop offset=\"100%\" stop-color=\"#FBBF24\" />
            </linearGradient>
        </defs>
        <!-- Cadre squircle officiel Bayan -->
        <rect x=\"3\" y=\"3\" width=\"94\" height=\"94\" rx=\"24\" fill=\"url(#bg_{$uid})\" stroke=\"url(#grad_{$uid})\" stroke-width=\"2.5\" />
        <!-- Silhouette architecturale classique -->
        <path d=\"M22 76 L22 42 L50 24 L78 42 L78 76 Z\" fill=\"none\" stroke=\"url(#grad_{$uid})\" stroke-width=\"2\" stroke-linejoin=\"round\" opacity=\"0.4\" />
        <path d=\"M50 24 L50 76\" stroke=\"url(#grad_{$uid})\" stroke-width=\"1.5\" opacity=\"0.25\" />
        <path d=\"M22 56 L78 56\" stroke=\"url(#grad_{$uid})\" stroke-width=\"1.5\" opacity=\"0.2\" />
        <!-- Étoile marocaine subtile au sommet -->
        <polygon points=\"50,14 51.5,18 56,18 52.5,20.5 54,25 50,22 46,25 47.5,20.5 44,18 48.5,18\" fill=\"url(#gold_{$uid})\" opacity=\"0.9\" />
        <!-- Monogramme Poppins des initiales de l'immeuble -->
        <text x=\"50\" y=\"63\" text-anchor=\"middle\" dominant-baseline=\"central\" fill=\"url(#grad_{$uid})\" font-family=\"'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif\" font-size=\"30\" font-weight=\"800\" letter-spacing=\"-0.5\">{$initials}</text>
    </svg>";
}

// ============================================================================
// 3. RÉSOLUTION DES LOGOS PERSONNALISÉS ET CONTRÔLE D'EXISTENCE PHYSIQUE
// ============================================================================

/**
 * Résout et valide l'URL du logo personnalisé de la résidence.
 *
 * Effectue un contrôle strict de sécurité et d'intégrité :
 * 1. Rejette les placeholders distants Unsplash qui génèrent des images brisées.
 * 2. Valide les data:image URI base64.
 * 3. Vérifie l'existence physique du fichier sur le disque local pour les chemins relatifs.
 * 4. Valide le format URL via filter_var.
 *
 * @param  string|null  $logoUrl  Chemin ou URL stocké en base de données
 * @return string|null URL relative ou absolue valide, ou NULL si absent/invalide
 */
function resolveResidenceLogo(?string $logoUrl): ?string
{
    if (empty($logoUrl)) {
        return null;
    }

    $logoUrl = trim($logoUrl);

    // Rejeter les anciens liens d'exemple externes Unsplash qui causent des erreurs d'image brisée
    if (str_contains($logoUrl, 'images.unsplash.com')) {
        return null;
    }

    // Accepter directement les URI encodées en base64
    if (str_starts_with($logoUrl, 'data:image/')) {
        return $logoUrl;
    }

    // Normalisation des chemins relatifs locaux (ex: uploads/logos/residence.png)
    $normalized = ltrim($logoUrl, '/\\');
    if (str_starts_with($normalized, 'Syndic/')) {
        $normalized = substr($normalized, 7);
    }

    // Vérification de l'existence physique du fichier sur le disque serveur
    $fullDiskPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    if (file_exists($fullDiskPath) && ! is_dir($fullDiskPath)) {
        return '/Syndic/'.$normalized;
    }

    // Si le chemin pointe sous uploads/ ou assets/ mais n'existe pas physiquement, forcer le fallback SVG
    if (str_starts_with($normalized, 'uploads/') || str_starts_with($normalized, 'assets/')) {
        return null;
    }

    // URL externe valide et accessible
    if (filter_var($logoUrl, FILTER_VALIDATE_URL)) {
        return $logoUrl;
    }

    return null;
}

// ============================================================================
// 4. COMPOSANTS D'AFFICHAGE AVEC FALLBACK ROBUSTE ANTI-IMAGE BRISÉE
// ============================================================================

/**
 * Rendu du bloc logo de la résidence pour barre de navigation (avec fallback garanti anti-image brisée).
 *
 * Si un logo personnalisé existe, il est rendu avec un écouteur JS onerror
 * pour basculer automatiquement sur le SVG en cas d'erreur de chargement réseau.
 *
 * @param  array<string, mixed>  $residence  Enregistrement complet de la copropriété
 * @param  int  $maxHeight  Hauteur maximale en pixels
 * @param  bool  $showName  Affichage textuel du nom à droite du logo
 * @return string Balisage HTML complet du badge
 */
function renderResidenceLogoBadge(array $residence, int $maxHeight = 36, bool $showName = true): string
{
    $customLogo = resolveResidenceLogo($residence['logo_url'] ?? null);
    $resName = htmlspecialchars((string) ($residence['nom'] ?? 'Résidence'));
    $placeholderSvg = getResidenceLogoPlaceholderSvg($residence, (int) ($maxHeight * 1.05));

    if ($customLogo !== null) {
        return "
        <div class=\"flex items-center gap-2.5\">
            <div class=\"p-1 rounded-xl bg-white/90 dark:bg-zinc-900/90 border border-slate-200 dark:border-zinc-800 shadow-sm flex items-center justify-center shrink-0\">
                <img src=\"{$customLogo}\" alt=\"Logo {$resName}\" class=\"max-h-[{$maxHeight}px] w-auto object-contain rounded-lg\" onerror=\"this.style.display='none'; this.nextElementSibling.style.display='inline-block';\" />
                <div style=\"display:none;\">{$placeholderSvg}</div>
            </div>
            ".($showName ? "<span class=\"font-bold text-sm text-slate-900 dark:text-white truncate\">{$resName}</span>" : '').'
        </div>';
    }

    // Logo Placeholder officiel par défaut pour la copropriété
    return "
    <div class=\"flex items-center gap-2.5\">
        {$placeholderSvg}
        ".($showName ? "<span class=\"font-bold text-sm text-slate-900 dark:text-white truncate\">{$resName}</span>" : '').'
    </div>';
}

/**
 * Rendu du logo principal centré (sur cartes de connexion et aperçu) avec garantie anti-image brisée.
 *
 * @param  array<string, mixed>  $residence  Enregistrement de la copropriété
 * @param  int  $size  Dimension du logo en pixels
 * @return string Balisage HTML/SVG complet
 */
function renderResidenceLogoMain(array $residence, int $size = 64): string
{
    $customLogo = resolveResidenceLogo($residence['logo_url'] ?? null);
    $resName = htmlspecialchars((string) ($residence['nom'] ?? 'Copropriété'));
    $placeholderSvg = getResidenceLogoPlaceholderSvg($residence, $size);

    if ($customLogo !== null) {
        return "
        <div class=\"inline-flex flex-col items-center justify-center\">
            <div class=\"p-2 rounded-2xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm inline-block\">
                <img src=\"{$customLogo}\" alt=\"Logo {$resName}\" class=\"h-14 w-auto object-contain rounded-xl\" onerror=\"this.parentElement.style.display='none'; this.parentElement.nextElementSibling.style.display='inline-block';\" />
            </div>
            <div style=\"display:none;\">
                {$placeholderSvg}
            </div>
        </div>";
    }

    return $placeholderSvg;
}

// ============================================================================
// 5. BALISES D'EN-TÊTE FAVICON MULTI-RÉSOLUTION POUR NAVIGATEUR
// ============================================================================

/**
 * Rendu des balises HTML d'en-tête pour le favicon officiel Bayan Gestion.
 *
 * Fournit le jeu d'icônes multi-résolution complet :
 * - Icône vectorielle SVG (navigateurs modernes Chrome, Edge, Firefox, Safari)
 * - Icône matricielle PNG 32x32 (compatibilité et favoris)
 * - Apple Touch Icon 180x180 (écrans Retina et raccourcis mobiles iOS)
 * - Raccourci ICO standard (fallback racine)
 *
 * @param  string  $prefix  Préfixe du chemin web (par défaut '/Syndic/')
 * @return string Balises HTML prêtes à l'injection dans <head>
 */
function renderBayanFaviconTags(string $prefix = '/Syndic/'): string
{
    $p = rtrim($prefix, '/').'/';

    return "    <!-- Favicon Officiel Bayan Gestion -->\n"
         ."    <link rel=\"icon\" type=\"image/svg+xml\" href=\"{$p}assets/img/bayan_icon.svg\">\n"
         ."    <link rel=\"alternate icon\" type=\"image/png\" sizes=\"32x32\" href=\"{$p}assets/img/bayan_icon_32.png\">\n"
         ."    <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"{$p}assets/img/bayan_icon_apple.png\">\n"
         ."    <link rel=\"shortcut icon\" href=\"{$p}favicon.ico\">\n";
}

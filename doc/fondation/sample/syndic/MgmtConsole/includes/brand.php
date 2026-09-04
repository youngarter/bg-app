<?php

/**
 * ============================================================================
 * SYNDIC CONNECT - CONSOLE MASTER : CHARTE VISUELLE & LOGOTYPES OFFICIELS
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * IDENTITÉ CHROMATIQUE & ÉLÉMENTS DE MARQUE MAÎTRESSE :
 * ----------------------------------------------------------------------------
 * Ce module définit les générateurs SVG officiels de l'éditeur Bayan Gestion
 * utilisés au niveau du tableau de bord de supervision centrale multi-tenants.
 *
 * Palette chromatique appliquée :
 * - Deep Plum    : #1E0427 (Arrière-plan sombre de prestige)
 * - Magenta      : #D91C6E (Couleur maîtresse d'accentuation)
 * - Salmon       : #F26968 (Nuance intermédiaire lumineuse)
 * - Warm Orange  : #F27835 (Chaleur et dynamisme)
 * - Primrose     : #FDF8F5 (Fond clair de contraste)
 */

declare(strict_types=1);

// ============================================================================
// 1. GÉNÉRATION VECTORIELLE DU LOGOTYPE COMPLET BAYAN GESTION
// ============================================================================

/**
 * Retourne le balisage SVG officiel complet de Bayan Gestion avec typographie Poppins.
 *
 * @param  int  $height  Hauteur d'affichage du logo en pixels (défaut : 36px)
 * @param  string  $variant  Variante de contraste ('auto'|'dark'|'light')
 * @param  bool  $withTagline  Affichage ou masquage du slogan de marque
 * @return string Code SVG/HTML prêt à l'insertion
 */
function getBayanLogoSvg(int $height = 36, string $variant = 'auto', bool $withTagline = false): string
{
    // Identifiant aléatoire évitant les collisions de masque SVG
    $id = 'bg_'.bin2hex(random_bytes(3));

    // Sélection des styles de contraste selon l'ambiance
    $textClass = ($variant === 'dark') ? 'text-white' : (($variant === 'light') ? 'text-[#1E0427]' : 'text-slate-900 dark:text-white');
    $subClass = ($variant === 'dark') ? 'text-slate-300' : (($variant === 'light') ? 'text-[#D91C6E]' : 'text-[#D91C6E] dark:text-[#F26968]');

    // Slogan officiel d'excellence
    $taglineHtml = $withTagline ? "
        <div class=\"text-[9px] uppercase tracking-wider font-medium opacity-75 $subClass\">
            Excellence in every detail &bull; Trust in every detail
        </div>" : '';

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

// ============================================================================
// 2. SYMBOLE VECTORIEL : RUBAN ORIGAMI 'b' STYLISÉ
// ============================================================================

/**
 * Retourne le symbole iconique ruban origami 'b' de Bayan Gestion.
 *
 * Utilise un dégradé quadri-stops (#C71F67 -> #D91C6E -> #F26968 -> #F27835).
 *
 * @param  int  $size  Dimension en pixels du conteneur carré
 * @return string Balisage SVG complet
 */
function getBayanIconSvg(int $size = 32): string
{
    $id = 'bayan_'.bin2hex(random_bytes(3));

    return "
    <svg viewBox=\"0 0 100 100\" width=\"{$size}\" height=\"{$size}\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\" class=\"shrink-0 filter drop-shadow-sm\">
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
        <path d=\"M46 44 C42.5 49 42.8 65 42.8 76.3 C44.5 80.2 46.8 83.7 49.8 86.6 C55.7 92.4 63.8 96 72.5 96 C88.2 96 100.5 83.2 100 67.5 C99.5 52.3 87.6 39.3 72.5 36.8 C62.8 35.2 53.5 38.2 46 44 Z\" fill=\"url(#{$id})\" opacity=\"0.2\" />
    </svg>";
}

// ============================================================================
// 3. BALISES D'EN-TÊTE FAVICON MULTI-RÉSOLUTION POUR NAVIGATEUR
// ============================================================================

/**
 * Rendu des balises HTML d'en-tête pour le favicon officiel Bayan Gestion.
 *
 * @param  string  $prefix  Préfixe du chemin web (par défaut '/Syndic/')
 * @return string Balises HTML prêtes à l'insertion dans <head>
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

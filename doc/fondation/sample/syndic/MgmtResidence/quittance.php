<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/quittance.php
 * TYPE           : Document Légal Imprimable / Reçu d'Encaissement Officiel
 * MODULE         : Trésorerie, Recouvrement des Charges & Décharge Libératoire
 * CADRE JURIDIQUE: Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002)
 *                  promulguant la Loi n° 18-00 relative au statut de la copropriété
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Cette page génère et met en page le reçu officiel de paiement (Quittance de
 * Copropriété) délivré par le Syndic au copropriétaire à la suite d'un versement
 * ou d'un encaissement effectif.
 *
 * Principes et caractéristiques techniques :
 * 1. Authentification & Cloisonnement :
 *    Accessible aux administrateurs du syndic ou délégués autorisés après
 *    résolution du contexte de copropriété (Tenant GUID).
 * 2. Données Contractuelles et Légales :
 *    - Numéro unique de quittance (ex: QUIT-2026-0001).
 *    - Identité complète du copropriétaire (Nom, Prénom, CIN).
 *    - Identification du lot et de sa quote-part (tantièmes / 10 000).
 *    - Modalités d'encaissement (Virement, Chèque avec réf, Versement espèces).
 *    - Montant certifié en Dirhams Marocains (MAD).
 * 3. Clause Libératoire :
 *    Mention expresse attestant que la somme a été créditée sur le compte bancaire
 *    séparé du syndicat des copropriétaires (ou sous réserve d'encaissement de chèque),
 *    valant décharge libératoire conformément aux dispositions légales marocaines.
 * 4. Optimisation pour Impression / Export PDF :
 *    CSS dédié (@media print) masquant les barres d'actions et ajustant les marges
 *    au format A4 standard avec zones d'émargement manuscrit et cachet officiel.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS DES DÉPENDANCES MÉTIER ET CONTRÔLE D'ACCÈS
// ----------------------------------------------------------------------------
// tenant_auth.php : Initialise la session cloisonnée et valide l'accès gestionnaire.
require_once __DIR__.'/includes/tenant_auth.php';

// brand.php : Utilitaires graphiques pour la résolution du logo ou fallback SVG.
require_once __DIR__.'/includes/brand.php';

// ----------------------------------------------------------------------------
// RÉSOLUTIONS CONTEXTUELLES DU TENANT ET DU PARAMÈTRE D'ENCAISSEMENT
// ----------------------------------------------------------------------------
// Extraction du GUID du tenant actif depuis la session ou les paramètres GET.
$guid = TenantDB::resolveGuid();

// Récupération de la fiche signalétique de la copropriété (nom, adresse, RIB, etc.).
$res = TenantDB::getResidence();

// Résolution de l'URL du logo personnalisé s'il a été téléversé, ou null.
$customLogoUrl = resolveResidenceLogo($res['logo_url'] ?? null);

// Récupération de l'identifiant unique du paiement passé en URL ($_GET['id']).
$id = $_GET['id'] ?? '';

// ----------------------------------------------------------------------------
// INTERROGATION DE LA BASE DE DONNÉES DÉDIÉE DU TENANT
// ----------------------------------------------------------------------------
// Connexion PDO vers la base SQLite isolée de l'immeuble.
$pdo = TenantDB::getPdo();

// Requête préparée joignant le paiement, le copropriétaire et le lot associé
// afin de consolider l'ensemble des mentions requises sur la quittance légale.
$stmt = $pdo->prepare('
    SELECT p.*, c.nom as coproprietaire_nom, c.prenom as coproprietaire_prenom, c.cin, l.numero as lot_numero, l.tantiemes
    FROM paiements p
    LEFT JOIN coproprietaires c ON p.coproprietaire_id = c.id
    LEFT JOIN lots l ON p.lot_id = l.id
    WHERE p.id = ?
');
$stmt->execute([$id]);

// Récupération de l'enregistrement de paiement sous forme de tableau associatif.
$p = $stmt->fetch();

// Vérification de l'existence de l'enregistrement : interruption propre si inexistant.
if (! $p) {
    exit('Paiement introuvable.');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quittance de Paiement - <?= htmlspecialchars($p['numero_quittance']) ?> - <?= htmlspecialchars($res['nom']) ?></title>

    <!-- Favicon Officiel Bayan Gestion -->
    <link rel="icon" type="image/svg+xml" href="/Syndic/assets/img/bayan_icon.svg">
    <link rel="alternate icon" type="image/png" sizes="32x32" href="/Syndic/assets/img/bayan_icon_32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/Syndic/assets/img/bayan_icon_apple.png">
    <link rel="shortcut icon" href="/Syndic/favicon.ico">

    <!-- Chargement de la typographie Poppins pour un rendu typographique officiel élégant -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Styles généraux pour affichage écran */
        body { font-family: 'Poppins', 'Segoe UI', sans-serif; margin: 0; padding: 30px; color: #1e0427; background-color: #fdf8f5; }
        .receipt-card { max-width: 760px; margin: 0 auto; background: white; border: 1px solid #f0e4dc; border-radius: 20px; padding: 40px; box-shadow: 0 4px 20px rgba(217, 28, 110, 0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #D91C6E; padding-bottom: 20px; margin-bottom: 25px; }
        .logo-box { max-height: 60px; max-width: 180px; object-fit: contain; margin-bottom: 8px; border-radius: 8px; }
        .residence-name { font-size: 19px; font-weight: 800; color: #1e0427; }
        .residence-meta { font-size: 11.5px; color: #64748b; margin-top: 4px; line-height: 1.5; }
        .quittance-badge { text-align: right; }
        .quittance-title { font-size: 17px; font-weight: 800; color: #D91C6E; letter-spacing: 0.5px; }
        .quittance-num { font-family: monospace; font-size: 13px; font-weight: bold; color: #1e0427; margin-top: 2px; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; font-size: 12.5px; }
        .box { background: #fdf8f5; border: 1px solid #f0e4dc; border-radius: 14px; padding: 16px; }
        .box-title { font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #D91C6E; margin-bottom: 8px; letter-spacing: 0.5px; }
        .amount-box { background: linear-gradient(135deg, rgba(217, 28, 110, 0.08) 0%, rgba(242, 120, 53, 0.08) 100%); border: 2px solid #F5B8A8; border-radius: 16px; padding: 20px; text-align: center; margin-bottom: 25px; }
        .amount-label { font-size: 11.5px; font-weight: 700; color: #D91C6E; text-transform: uppercase; letter-spacing: 0.5px; }
        .amount-val { font-size: 30px; font-weight: 900; color: #1e0427; margin: 4px 0; font-family: 'Poppins', sans-serif; }
        .legal-text { font-size: 11px; color: #64748b; line-height: 1.6; text-align: justify; margin-bottom: 25px; border-top: 1px solid #f0e4dc; padding-top: 15px; }
        .signatures { display: flex; justify-content: space-between; font-size: 11.5px; margin-top: 35px; }
        .sig-box { width: 230px; border-top: 1px solid #cbd5e1; padding-top: 8px; text-align: center; }
        .footer-brand { display: flex; align-items: center; justify-content: space-between; font-size: 10.5px; color: #94a3b8; border-top: 1px solid #f0e4dc; padding-top: 12px; margin-top: 25px; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { background: linear-gradient(135deg, #D91C6E 0%, #F27835 100%); color: white; border: none; padding: 10px 26px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 13px; font-family: 'Poppins', sans-serif; box-shadow: 0 4px 12px rgba(217, 28, 110, 0.25); }

        /* Media query print : conversion propre en format papier officiel */
        @media print {
            body { background: white; padding: 0; }
            .receipt-card { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Barre d'action : déclencheur d'impression masqué lors de l'impression physique -->
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer la Quittance Officielle</button>
    </div>

    <!-- Carte principale de quittance -->
    <div class="receipt-card">
        <!-- En-tête : Logo, coordonnées juridiques de la copropriété et badge quittance -->
        <div class="header">
            <div>
                <?php if ($customLogoUrl) { ?>
                    <img src="<?= htmlspecialchars($customLogoUrl) ?>" alt="Logo Copropriété" class="logo-box" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div style="display:none; margin-bottom: 8px;"><?= getResidenceLogoPlaceholderSvg($res, 46) ?></div>
                <?php } else { ?>
                    <div style="margin-bottom: 8px;"><?= getResidenceLogoPlaceholderSvg($res, 46) ?></div>
                <?php } ?>
                <div class="residence-name"><?= htmlspecialchars($res['nom']) ?></div>
                <div class="residence-meta">
                    <?= htmlspecialchars($res['adresse']) ?><br>
                    <?= htmlspecialchars($res['ville']) ?> &bull; Code Copropriété : <strong><?= htmlspecialchars($res['code_unique']) ?></strong><br>
                    Titre Foncier Mère : <?= htmlspecialchars($res['titre_foncier_mere'] ?: 'TF Mère') ?> &bull; RIB : <?= htmlspecialchars($res['rib_bancaire']) ?>
                </div>
            </div>
            <div class="quittance-badge">
                <div class="quittance-title">QUITTANCE DE PAIEMENT</div>
                <div class="quittance-num"><?= htmlspecialchars($p['numero_quittance']) ?></div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Délivrée le <?= formatDateFR($p['date_paiement']) ?></div>
            </div>
        </div>

        <!-- Grille des détails : Informations du débiteur et détails de l'opération d'encaissement -->
        <div class="details-grid">
            <!-- Bloc 1 : Copropriétaire dépositaire -->
            <div class="box">
                <div class="box-title">Copropriétaire Dépositaire</div>
                <div style="font-size: 14.5px; font-weight: 700; color: #1e0427; margin-bottom: 4px;">
                    <?= htmlspecialchars(($p['coproprietaire_prenom'] ?? '').' '.($p['coproprietaire_nom'] ?? '')) ?>
                </div>
                <div>CIN : <strong><?= htmlspecialchars($p['cin'] ?: 'N/A') ?></strong></div>
                <div>Lot désigné : <strong>Lot <?= htmlspecialchars($p['lot_numero'] ?: 'N/A') ?></strong> (<?= $p['tantiemes'] ?? 0 ?> / 10 000 tantièmes)</div>
            </div>

            <!-- Bloc 2 : Modalités de règlement et affectation comptable -->
            <div class="box">
                <div class="box-title">Modalités d'Encaissement</div>
                <div>Date d'encaissement : <strong><?= formatDateFR($p['date_paiement']) ?></strong></div>
                <div>Mode de règlement : <strong><?= htmlspecialchars(MODE_PAIEMENT_LABELS[$p['mode_paiement']] ?? $p['mode_paiement']) ?></strong></div>
                <div>Référence : <strong style="font-family: monospace;"><?= htmlspecialchars($p['reference'] ?: 'SANS RÉF') ?></strong></div>
                <div>Affectation : <strong>Charges communes de copropriété</strong></div>
            </div>
        </div>

        <!-- Encadré mis en exergue : Montant net certifié reçu en Dirhams (MAD) -->
        <div class="amount-box">
            <div class="amount-label">Montant Net Reçu et Encaissé</div>
            <div class="amount-val"><?= formatMAD($p['montant']) ?></div>
            <div style="font-size: 11.5px; color: #64748b;">Soit la somme reçue au crédit du compte bancaire du Syndicat des Copropriétaires</div>
        </div>

        <!-- Mention légale obligatoire sous le Dahir n° 1-02-298 (Loi 18-00) -->
        <div class="legal-text">
            <strong>Décharge Légale Libératoire :</strong> Le Syndic en exercice atteste avoir reçu du copropriétaire ci-dessus désigné la somme indiquée, sous réserve de bon encaissement pour les chèques. La présente quittance vaut quittance libératoire pour les charges visées, conformément aux dispositions du Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002) portant promulgation de la loi n° 18-00 relative au statut de la copropriété des immeubles bâtis au Maroc.
        </div>

        <!-- Zone des signatures : Dépositaire et Syndic certifiant avec cachet -->
        <div class="signatures">
            <div class="sig-box">
                Le Copropriétaire<br>
                <em>(Pour Acquit)</em>
            </div>
            <div class="sig-box">
                Pour le Syndicat des Copropriétaires<br>
                <strong><?= htmlspecialchars($res['nom_syndic']) ?></strong><br>
                <em>(Signature et Cachet du Syndic)</em>
            </div>
        </div>

        <!-- Pied de page officiel du document imprimable -->
        <div class="footer-brand">
            <div><?= htmlspecialchars($res['nom']) ?> &bull; Dahir n° 1-02-298 (Loi 18-00)</div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span>Plateforme éditée par</span>
                <strong style="color: #D91C6E;">Bayan Gestion</strong>
            </div>
        </div>
    </div>
</body>
</html>

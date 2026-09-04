<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResident/quittance.php
 * TYPE           : Document Légal Imprimable / Espace Privatif Copropriétaire
 * MODULE         : Consultation & Téléchargement Quittances Personnelles
 * CADRE JURIDIQUE: Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002)
 *                  promulguant la Loi n° 18-00 relative au statut de la copropriété
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Cette page permet à un copropriétaire authentifié d'afficher et d'imprimer
 * son reçu libératoire officiel de paiement de charges.
 *
 * Règles de sécurité et d'isolation des données :
 * 1. Barrière d'Accès Résidentielle (requireResidentAuth) :
 *    Seul un utilisateur connecté au portail résident peut exécuter ce script.
 * 2. Contrôle de Propriété Strict (Perimeter Guard) :
 *    Vérifie formellement que l'ID copropriétaire du paiement (`p.coproprietaire_id`)
 *    correspond exactement à celui de la session active (`$user['coproprietaire_id']`).
 *    Tout contournement par manipulation du paramètre $_GET['id'] est bloqué
 *    immédiatement avec un message d'erreur d'autorisation.
 * 3. Navigation Adaptée :
 *    Intègre un bouton de retour sécurisé vers l'onglet "Mes Quittances" du
 *    portail résident ainsi que le bouton de commande d'impression papier/PDF.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS DES DÉPENDANCES ET CONTRÔLE D'ACCÈS DU RÉSIDENT
// ----------------------------------------------------------------------------
// resident_auth.php : Valide la session privative du résident dans ce tenant.
require_once __DIR__.'/includes/resident_auth.php';

// tenant_db.php : Couche d'accès aux données de la copropriété concernée.
require_once dirname(__DIR__).'/MgmtResidence/includes/tenant_db.php';

// brand.php : Gestionnaire du logo de la copropriété et des thèmes graphiques.
require_once dirname(__DIR__).'/MgmtResidence/includes/brand.php';

// ----------------------------------------------------------------------------
// RÉSOLUTIONS CONTEXTUELLES DU TENANT ET DU RÉSIDENT CONNECTÉ
// ----------------------------------------------------------------------------
// Résolution du GUID du tenant actif (dérivé de la session ou du paramètre tenant).
$guid = TenantDB::resolveGuid();

// Exige l'authentification du résident et retourne les données de session ($user).
$user = requireResidentAuth();

// Chargement des coordonnées signalétiques de l'immeuble.
$res = TenantDB::getResidence();

// Résolution de l'URL du logo officiel de la copropriété.
$customLogoUrl = resolveResidenceLogo($res['logo_url'] ?? null);

// Récupération de l'identifiant du paiement demandé en paramètre GET ($_GET['id']).
$id = $_GET['id'] ?? '';

// ----------------------------------------------------------------------------
// INTERROGATION DE LA BASE DE DONNÉES DU TENANT
// ----------------------------------------------------------------------------
// Connexion PDO vers la base SQLite isolée.
$pdo = TenantDB::getPdo();

// Requête préparée extrayant le paiement avec jointure sur le copropriétaire et le lot.
$stmt = $pdo->prepare('
    SELECT p.*, c.nom as coproprietaire_nom, c.prenom as coproprietaire_prenom, c.cin, l.numero as lot_numero, l.tantiemes
    FROM paiements p
    LEFT JOIN coproprietaires c ON p.coproprietaire_id = c.id
    LEFT JOIN lots l ON p.lot_id = l.id
    WHERE p.id = ?
');
$stmt->execute([$id]);
$p = $stmt->fetch();

// Si le reçu n'existe pas dans cette base de copropriété, arrêt du script.
if (! $p) {
    exit('Paiement introuvable.');
}

// ----------------------------------------------------------------------------
// CONTRÔLE DE SÉCURITÉ DE CONFIDENTIALITÉ (ISOLATION RÉSIDENTIELLE)
// ----------------------------------------------------------------------------
// Empêche un résident d'accéder à la quittance d'un tiers en modifiant l'ID.
if (! empty($user['coproprietaire_id']) && $p['coproprietaire_id'] !== $user['coproprietaire_id']) {
    exit('Accès non autorisé : Vous ne pouvez consulter que vos propres quittances de paiement.');
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

    <!-- Intégration de la police Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Styles écran pour la consultation de la quittance */
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
        .no-print { text-align: center; margin-bottom: 20px; display: flex; justify-content: center; gap: 10px; }
        .btn-print { background: linear-gradient(135deg, #D91C6E 0%, #F27835 100%); color: white; border: none; padding: 10px 24px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 13px; font-family: 'Poppins', sans-serif; box-shadow: 0 4px 12px rgba(217, 28, 110, 0.25); text-decoration: none; display: inline-flex; align-items: center; }
        .btn-back { background: white; color: #1e0427; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 13px; font-family: 'Poppins', sans-serif; text-decoration: none; display: inline-flex; align-items: center; }

        /* Media query print : adaptation au document papier légal */
        @media print {
            body { background: white; padding: 0; }
            .receipt-card { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Boutons d'action : Retour portail et déclencheur d'impression -->
    <div class="no-print">
        <a href="index.php?tenant=<?= urlencode($guid) ?>&page=paiements" class="btn-back">&larr; Retour à Mes Quittances</a>
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer la Quittance Officielle</button>
    </div>

    <!-- Carte de quittance libératoire -->
    <div class="receipt-card">
        <!-- En-tête : Informations légales de l'immeuble et numéro officiel du reçu -->
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

        <!-- Détails du règlement : Identité du copropriétaire et modalités d'encaissement -->
        <div class="details-grid">
            <div class="box">
                <div class="box-title">Copropriétaire Dépositaire</div>
                <div style="font-size: 14.5px; font-weight: 700; color: #1e0427; margin-bottom: 4px;">
                    <?= htmlspecialchars(($p['coproprietaire_prenom'] ?? '').' '.($p['coproprietaire_nom'] ?? '')) ?>
                </div>
                <div>CIN : <strong><?= htmlspecialchars($p['cin'] ?: 'N/A') ?></strong></div>
                <div>Lot désigné : <strong>Lot <?= htmlspecialchars($p['lot_numero'] ?: 'N/A') ?></strong> (<?= $p['tantiemes'] ?? 0 ?> / 10 000 tantièmes)</div>
            </div>

            <div class="box">
                <div class="box-title">Modalités d'Encaissement</div>
                <div>Date d'encaissement : <strong><?= formatDateFR($p['date_paiement']) ?></strong></div>
                <div>Mode de règlement : <strong><?= htmlspecialchars(MODE_PAIEMENT_LABELS[$p['mode_paiement']] ?? $p['mode_paiement']) ?></strong></div>
                <div>Référence : <strong style="font-family: monospace;"><?= htmlspecialchars($p['reference'] ?: 'SANS RÉF') ?></strong></div>
                <div>Affectation : <strong>Charges communes de copropriété</strong></div>
            </div>
        </div>

        <!-- Montant net encaissé mis en exergue -->
        <div class="amount-box">
            <div class="amount-label">Montant Net Reçu et Encaissé</div>
            <div class="amount-val"><?= formatMAD($p['montant']) ?></div>
            <div style="font-size: 11.5px; color: #64748b;">Soit la somme reçue au crédit du compte bancaire du Syndicat des Copropriétaires</div>
        </div>

        <!-- Mention légale obligatoire (Dahir n° 1-02-298) -->
        <div class="legal-text">
            <strong>Décharge Légale Libératoire :</strong> Le Syndic en exercice atteste avoir reçu du copropriétaire ci-dessus désigné la somme indiquée, sous réserve de bon encaissement pour les chèques. La présente quittance vaut quittance libératoire pour les charges visées, conformément aux dispositions du Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002) portant promulgation de la loi n° 18-00 relative au statut de la copropriété des immeubles bâtis au Maroc.
        </div>

        <!-- Emplacements des signatures officielles -->
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

        <!-- Pied de page officiel du reçu -->
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

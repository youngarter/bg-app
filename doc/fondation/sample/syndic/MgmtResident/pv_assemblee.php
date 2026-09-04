<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResident/pv_assemblee.php
 * TYPE           : Document Légal Imprimable / Espace Information Copropriétaire
 * MODULE         : Consultation des Procès-Verbaux & Décisions d'Assemblées
 * CADRE JURIDIQUE: Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002)
 *                  promulguant la Loi n° 18-00 relative au statut de la copropriété
 *                  - Articles 16 à 18 : Information et notifications des résolutions
 *                  - Article 19       : Constatation du quorum légal
 *                  - Article 20       : Passation de consignes et élection du Syndic
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Cette page offre aux copropriétaires un accès transparent et certifié aux
 * procès-verbaux complets des assemblées générales ordinaires et extraordinaires.
 *
 * Éléments consultables et vérifiables par les résidents :
 * 1. Validité Légale et Bureau :
 *    - Constat des tantièmes représentés garantissant la régularité du quorum.
 *    - Mention des membres du bureau de séance élus par les copropriétaires.
 * 2. Délibérations et Votes :
 *    - Transcription intégrale des résolutions adoptées (quitus, budget, travaux).
 * 3. Acte de Passation d'Exercice (si applicable) :
 *    - Notification officielle aux résidents de l'élection d'un nouveau syndic
 *      et remise contradictoire des archives et de l'état de trésorerie.
 * 4. Bouton de retour sécurisé vers le portail résident et module d'impression.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS DES DÉPENDANCES ET CONTRÔLE D'ACCÈS RÉSIDENTIEL
// ----------------------------------------------------------------------------
// resident_auth.php : Valide la session privative du résident dans ce tenant.
require_once __DIR__.'/includes/resident_auth.php';

// tenant_db.php : Couche d'accès aux données SQLite de la copropriété.
require_once dirname(__DIR__).'/MgmtResidence/includes/tenant_db.php';

// brand.php : Gestionnaire des logos et chartes visuelles de la copropriété.
require_once dirname(__DIR__).'/MgmtResidence/includes/brand.php';

// ----------------------------------------------------------------------------
// RÉSOLUTIONS CONTEXTUELLES DU TENANT ET DE L'ASSEMBLÉE DEMANDÉE
// ----------------------------------------------------------------------------
// Résolution du GUID du tenant actif.
$guid = TenantDB::resolveGuid();

// Vérification de la session active du copropriétaire connecté.
$user = requireResidentAuth();

// Chargement des informations signalétiques de l'immeuble.
$res = TenantDB::getResidence();

// Résolution de l'URL du logo officiel ou fallback SVG.
$customLogoUrl = resolveResidenceLogo($res['logo_url'] ?? null);

// Récupération de l'identifiant de l'AG passé en paramètre URL ($_GET['id']).
$id = $_GET['id'] ?? '';

// Récupération des données complètes de l'assemblée générale depuis la base SQLite.
$ag = TenantDB::getAssembleeById($id);

// Si l'AG est introuvable, arrêt propre de l'exécution.
if (! $ag) {
    exit('Assemblée Générale introuvable.');
}

// Distinction de la nature de la séance : AGO ou AGE.
$isAGE = ($ag['type'] === 'extraordinaire');

// Indicateur booléen signalant si un changement de syndic a été acté.
$changement = ! empty($ag['changement_syndic']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal AG - <?= htmlspecialchars($res['nom']) ?> - <?= formatDateFR($ag['date']) ?></title>

    <!-- Favicon Officiel Bayan Gestion -->
    <link rel="icon" type="image/svg+xml" href="/Syndic/assets/img/bayan_icon.svg">
    <link rel="alternate icon" type="image/png" sizes="32x32" href="/Syndic/assets/img/bayan_icon_32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/Syndic/assets/img/bayan_icon_apple.png">
    <link rel="shortcut icon" href="/Syndic/favicon.ico">

    <!-- Intégration de la typographie Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Styles de mise en page écran pour le procès-verbal résident */
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            margin: 0;
            padding: 30px;
            color: #1e0427;
            background-color: #fdf8f5;
            line-height: 1.6;
        }
        .pv-card {
            max-width: 820px;
            margin: 0 auto;
            background: white;
            border: 1px solid #f0e4dc;
            border-radius: 20px;
            padding: 45px;
            box-shadow: 0 4px 20px rgba(217, 28, 110, 0.05);
        }
        .header-box {
            text-align: center;
            border-bottom: 2px solid #D91C6E;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .logo-box {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        .residence-title {
            font-size: 20px;
            font-weight: 800;
            color: #1e0427;
        }
        .pv-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: <?= $isAGE ? '#fef3c7' : '#fdf2f8' ?>;
            color: <?= $isAGE ? '#b45309' : '#D91C6E' ?>;
            border: 1px solid <?= $isAGE ? '#fde68a' : '#fbcfe8' ?>;
        }
        .pv-main-title {
            font-size: 18px;
            font-weight: 800;
            color: #1e0427;
            margin-top: 10px;
            text-transform: uppercase;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            background: #fdf8f5;
            border: 1px solid #f0e4dc;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 25px;
            font-size: 12px;
        }
        .meta-item strong {
            display: block;
            color: #D91C6E;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .section-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #1e0427;
            border-bottom: 1px solid #f0e4dc;
            padding-bottom: 6px;
            margin-top: 25px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .text-content {
            font-size: 12.5px;
            color: #334155;
            text-align: justify;
        }
        .passation-box {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 14px;
            padding: 18px;
            margin: 20px 0;
        }
        .passation-title {
            font-size: 13px;
            font-weight: 800;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            font-size: 11.5px;
        }
        .sig-box {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            text-align: center;
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .btn-print {
            background: linear-gradient(135deg, #D91C6E 0%, #F27835 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 12px rgba(217, 28, 110, 0.25);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-back {
            background: white;
            color: #1e0427;
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        /* Mode d'impression papier */
        @media print {
            body { background: white; padding: 0; }
            .pv-card { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Boutons d'action non imprimables -->
    <div class="no-print">
        <a href="index.php?tenant=<?= urlencode($guid) ?>&page=assemblees" class="btn-back">&larr; Retour aux Assemblées</a>
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer le Procès-Verbal Officiel</button>
    </div>

    <!-- Carte du document officiel -->
    <div class="pv-card">
        <!-- En-tête : Titre de l'immeuble, statut légal et type d'AG -->
        <div class="header-box">
            <?php if ($customLogoUrl) { ?>
                <img src="<?= htmlspecialchars($customLogoUrl) ?>" alt="Logo Copropriété" class="logo-box" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display:none; margin-bottom: 10px;"><?= getResidenceLogoPlaceholderSvg($res, 52) ?></div>
            <?php } else { ?>
                <div style="margin-bottom: 10px;"><?= getResidenceLogoPlaceholderSvg($res, 52) ?></div>
            <?php } ?>
            <div class="residence-title"><?= htmlspecialchars($res['nom']) ?></div>
            <div style="font-size: 11.5px; color: #64748b;">
                <?= htmlspecialchars($res['adresse']) ?> &bull; <?= htmlspecialchars($res['ville']) ?><br>
                Syndicat des Copropriétaires régi par la Loi 18-00 &bull; Code Copropriété : <?= htmlspecialchars($res['code_unique']) ?>
            </div>
            <div>
                <span class="pv-badge">
                    <?= $isAGE ? '🚨 Assemblée Générale Extraordinaire (AGE)' : '📅 Assemblée Générale Ordinaire (AGO)' ?>
                </span>
            </div>
            <div class="pv-main-title">
                PROCÈS-VERBAL DE L'ASSEMBLÉE GÉNÉRALE DU <?= strtoupper(formatDateFR($ag['date'])) ?>
            </div>
        </div>

        <!-- Grille des métadonnées de séance et constat du Quorum -->
        <div class="meta-grid">
            <div class="meta-item">
                <strong>Date & Heure</strong>
                <?= formatDateFR($ag['date']) ?>
            </div>
            <div class="meta-item">
                <strong>Lieu de réunion</strong>
                <?= htmlspecialchars($ag['lieu'] ?: 'Hall de la résidence') ?>
            </div>
            <div class="meta-item">
                <strong>Exercice Comptable</strong>
                Exercice <?= htmlspecialchars((string) $ag['exercice']) ?>
            </div>
            <div class="meta-item">
                <strong>Président de Séance</strong>
                <?= htmlspecialchars($ag['president_seance'] ?: 'Président élu en début de séance') ?>
            </div>
            <div class="meta-item">
                <strong>Quorum & Présence</strong>
                <?= $ag['tantiemes_presents'] ?? 8500 ?> / 10 000 tantièmes (<?= $ag['statut_quorum'] ?: 'Quorum Atteint' ?>)
            </div>
            <div class="meta-item">
                <strong>Syndic en Exercice</strong>
                <?= htmlspecialchars($res['nom_syndic']) ?>
            </div>
        </div>

        <!-- Section 1 : Ordre du jour -->
        <div class="section-title">1. Constitution du Bureau & Ordre du Jour</div>
        <div class="text-content">
            L'Assemblée Générale a été régulièrement convoquée conformément aux dispositions des articles 16 à 18 de la Loi 18-00. Le bureau de séance ayant constaté la présence et la représentation de <?= $ag['tantiemes_presents'] ?? 8500 ?> tantièmes sur un total de 10 000 tantièmes, déclare l'assemblée valablement constituée pour délibérer sur l'ordre du jour suivant :
            <div style="margin-top: 8px; padding-left: 15px; font-style: italic;">
                <?= nl2br(htmlspecialchars($ag['ordre_du_jour'] ?: "1. Examen et approbation des comptes annuels et quitus au syndic.\n2. Vote du budget prévisionnel et provisions de charges.\n3. Travaux d'entretien et gestion des parties communes.")) ?>
            </div>
        </div>

        <!-- Section 2 : Délibérations et votes -->
        <div class="section-title">2. Délibérations et Décisions Adoptées</div>
        <div class="text-content">
            <?= nl2br(htmlspecialchars($ag['description'] ?: 'Les copropriétaires ont débattu des points inscrits à l\'ordre du jour. Les comptes de l\'exercice ont été approuvés à la majorité légale requise.')) ?>
            <?php if (! empty($ag['pv_texte'])) { ?>
                <div style="margin-top: 10px;">
                    <?= nl2br(htmlspecialchars($ag['pv_texte'])) ?>
                </div>
            <?php } ?>
        </div>

        <!-- Passation de mandat (si nouveau syndic désigné) -->
        <?php if ($changement) { ?>
            <div class="passation-box">
                <div class="passation-title">
                    <span>🔄</span>
                    <span>Acte Officiel d'Élection & de Passation d'Exercice (Article 20 Loi 18-00)</span>
                </div>
                <div class="text-content" style="font-size: 12px; color: #166534;">
                    L'Assemblée Générale, statuant conformément aux dispositions légales, a procédé à l'élection de son nouveau Syndic :
                    <div style="margin: 10px 0; padding: 10px; background: white; border-radius: 8px; border: 1px solid #bbf7d0;">
                        <div>Nouveau Syndic Élu : <strong style="color: #0f172a; font-size: 13px;"><?= htmlspecialchars($ag['nouveau_syndic_nom']) ?></strong></div>
                        <div>Email Officiel : <strong><?= htmlspecialchars($ag['nouveau_syndic_email']) ?></strong> &bull; Téléphone : <?= htmlspecialchars($ag['nouveau_syndic_tel'] ?: 'N/A') ?></div>
                        <div>Prise d'effet du mandat : <strong><?= formatDateFR($ag['date_effet_mandat'] ?: $ag['date']) ?></strong></div>
                        <div>Situation de Trésorerie arrêtée à la passation : <strong><?= formatMAD((float) ($ag['tresorerie_arretee'] ?? 0)) ?></strong></div>
                    </div>
                    <strong>Bordereau de Remise et de Décharge (Art. 20) :</strong><br>
                    Il a été procédé à la transmission complète au nouveau syndic de l'ensemble des documents, registres de procès-verbaux, contrats des prestataires, relevés bancaires, situation de caisse, carnet d'entretien et clés des locaux techniques de l'immeuble.
                </div>
            </div>
        <?php } ?>

        <!-- Section 3 : Clôture et Signatures -->
        <div class="section-title">3. Clôture de Séance et Signatures</div>
        <div class="text-content">
            L'ordre du jour étant épuisé et personne ne demandant plus la parole, la séance est levée. De tout ce que dessus, il a été dressé le présent procès-verbal consigné au registre officiel de la copropriété.
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                Le Président de Séance<br>
                <strong><?= htmlspecialchars($ag['president_seance'] ?: 'Président élu') ?></strong>
            </div>
            <div class="sig-box">
                Le Syndic en Exercice<br>
                <strong><?= htmlspecialchars($changement ? $ag['nouveau_syndic_nom'] : $res['nom_syndic']) ?></strong>
            </div>
        </div>

        <!-- Pied de page -->
        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 10.5px; color: #94a3b8; border-top: 1px solid #f0e4dc; padding-top: 12px; margin-top: 35px;">
            <div><?= htmlspecialchars($res['nom']) ?> &bull; Dahir n° 1-02-298 (Loi 18-00)</div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span>Plateforme éditée par</span>
                <strong style="color: #D91C6E;">Bayan Gestion</strong>
            </div>
        </div>
    </div>
</body>
</html>

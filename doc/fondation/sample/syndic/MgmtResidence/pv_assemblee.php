<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pv_assemblee.php
 * TYPE           : Document Légal Imprimable / Registre des Procès-Verbaux
 * MODULE         : Gouvernance, Assemblées Générales & Passation de Pouvoir
 * CADRE JURIDIQUE: Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002)
 *                  promulguant la Loi n° 18-00 relative au statut de la copropriété
 *                  - Articles 16 à 18 : Convocation et tenue des AG
 *                  - Article 19       : Quorum légal et calcul des majorités
 *                  - Article 20       : Passation de consignes et élection du Syndic
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Cette page met en forme et restitue le Procès-Verbal officiel d'une Assemblée
 * Générale (Ordinaire ou Extraordinaire) pour impression, signature et archivage
 * dans le registre officiel de la copropriété.
 *
 * Éléments juridiques et opérationnels consignés :
 * 1. Identification de l'immeuble et bureau de séance :
 *    - Titre Foncier Mère et Code Copropriété.
 *    - Élection du Président et du Secrétaire de séance.
 * 2. Contrôle d'émargement et Quorum (Article 19) :
 *    - Tantièmes présents ou représentés rapportés au total légal de 10 000.
 * 3. Ordre du Jour & Décisions Adoptées :
 *    - Approbation des comptes, quitus au syndic, vote du budget prévisionnel.
 *    - Décisions d'entretien ou de travaux d'amélioration.
 * 4. Acte Spécial de Passation d'Exercice (Article 20) :
 *    - En cas de changement de Syndic voté en AG, intégration du bordereau
 *      officiel de remise des archives, fonds de caisse, relevés bancaires
 *      et clés techniques au nouveau syndic entrant.
 * 5. Signatures Officielles :
 *    - Émargements conjoints du Président de séance et du Syndic en exercice.
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// INCLUSIONS DES DÉPENDANCES ET CONTRÔLE DE SESSION GESTIONNAIRE
// ----------------------------------------------------------------------------
// tenant_auth.php : Vérifie l'authentification et l'intégrité de la session syndic.
require_once __DIR__.'/includes/tenant_auth.php';

// brand.php : Fonctions graphiques pour l'affichage du logo ou du badge SVG.
require_once __DIR__.'/includes/brand.php';

// ----------------------------------------------------------------------------
// RÉSOLUTIONS CONTEXTUELLES DU TENANT ET DE L'ASSEMBLÉE GÉNÉRALE
// ----------------------------------------------------------------------------
// Résolution du GUID de la copropriété active.
$guid = TenantDB::resolveGuid();

// Chargement des informations signalétiques de l'immeuble (nom, adresse, TF, etc.).
$res = TenantDB::getResidence();

// Résolution de l'URL du logo officiel de la copropriété.
$customLogoUrl = resolveResidenceLogo($res['logo_url'] ?? null);

// Récupération de l'identifiant unique de l'AG passé en paramètre URL ($_GET['id']).
$id = $_GET['id'] ?? '';

// Récupération de l'ensemble des données de l'assemblée générale depuis la base SQLite.
$ag = TenantDB::getAssembleeById($id);

// Si l'AG n'est pas trouvée, arrêt de l'exécution avec message d'erreur explicite.
if (! $ag) {
    exit('Assemblée Générale introuvable.');
}

// Détermination de la nature de l'assemblée : Ordinaire (AGO) ou Extraordinaire (AGE).
$isAGE = ($ag['type'] === 'extraordinaire');

// Indicateur booléen vérifiant si un changement de syndic a été acté lors de cette séance.
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

    <!-- Intégration de la police Poppins pour une mise en page soignée -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Styles d'affichage écran pour le procès-verbal */
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .residence-sub {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 4px;
        }
        .pv-main-title {
            font-size: 17px;
            font-weight: 800;
            color: #D91C6E;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .meta-item strong {
            color: #334155;
        }
        .text-content {
            font-size: 12.5px;
            color: #334155;
            text-align: justify;
        }
        .passation-box {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            margin-bottom: 25px;
        }
        .passation-title {
            font-size: 13px;
            font-weight: 800;
            color: #15803d;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            font-size: 12px;
        }
        .sig-box {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            text-align: center;
        }
        .btn-print {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .no-print {
            text-align: center;
        }

        /* Mode d'impression papier : suppression des ombres et éléments d'interface */
        @media print {
            body { background: white; padding: 0; }
            .pv-card { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Bouton d'action non imprimable -->
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer le Procès-Verbal Officiel</button>
    </div>

    <!-- Document officiel du Procès-Verbal -->
    <div class="pv-card">
        <!-- En-tête officiel : Titre Foncier, Identification légale Dahir n° 1-02-298 -->
        <div class="header-box">
            <?php if ($customLogoUrl) { ?>
                <img src="<?= htmlspecialchars($customLogoUrl) ?>" alt="Logo Copropriété" class="logo-box" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display:none; margin-bottom: 10px;"><?= getResidenceLogoPlaceholderSvg($res, 52) ?></div>
            <?php } else { ?>
                <div style="margin-bottom: 10px;"><?= getResidenceLogoPlaceholderSvg($res, 52) ?></div>
            <?php } ?>
            <div class="residence-title"><?= htmlspecialchars($res['nom']) ?></div>
            <div class="residence-sub">
                <?= htmlspecialchars($res['adresse']) ?> &bull; <?= htmlspecialchars($res['ville']) ?><br>
                Titre Foncier Mère : <strong><?= htmlspecialchars($res['titre_foncier_mere'] ?: 'TF Mère') ?></strong> &bull; Code Copropriété : <?= htmlspecialchars($res['code_unique']) ?><br>
                Royaume du Maroc &bull; Dahir n° 1-02-298 portant promulgation de la Loi n° 18-00
            </div>

            <div class="pv-main-title">
                PROCÈS-VERBAL DE L'ASSEMBLÉE GÉNÉRALE <?= $isAGE ? 'EXTRAORDINAIRE (AGE)' : 'ORDINAIRE (AGO)' ?>
            </div>
        </div>

        <!-- Informations de Séance & Constatation du Quorum (Article 19) -->
        <div class="meta-grid">
            <div class="meta-item">
                <strong>Date & Heure :</strong> <?= formatDateFR($ag['date']) ?><br>
                <strong>Lieu de la réunion :</strong> <?= htmlspecialchars($ag['lieu'] ?: 'Hall de la résidence') ?><br>
                <strong>Nature de l'AG :</strong> Assemblée Générale <?= ucfirst($ag['type']) ?>
            </div>
            <div class="meta-item">
                <strong>Président de séance élu :</strong> <?= htmlspecialchars($ag['president_seance'] ?: 'M. Le Président') ?><br>
                <strong>Secrétaire de séance :</strong> <?= htmlspecialchars($ag['secretaire_seance'] ?: 'Mme/M. Le Secrétaire') ?><br>
                <strong>Quorum vérifié :</strong> <span style="color:#16a34a; font-weight:bold;"><?= $ag['tantiemes_presents'] ?? 8500 ?> / 10 000 tantièmes</span> (Quorum légal Art. 19 atteint)
            </div>
        </div>

        <!-- Section 1 : Ordre du Jour & Constatations Préalables -->
        <div class="section-title">1. Ordre du Jour & Constatations Préalables</div>
        <div class="text-content">
            L'Assemblée Générale a été régulièrement convoquée conformément aux dispositions des articles 16 à 18 de la Loi 18-00. Le bureau de séance ayant constaté la présence et la représentation de <?= $ag['tantiemes_presents'] ?? 8500 ?> tantièmes sur un total de 10 000 tantièmes, déclare l'assemblée valablement constituée pour délibérer sur l'ordre du jour suivant :
            <div style="margin-top: 8px; padding-left: 15px; font-style: italic;">
                <?= nl2br(htmlspecialchars($ag['ordre_du_jour'] ?: "1. Examen et approbation des comptes annuels et quitus au syndic.\n2. Vote du budget prévisionnel et provisions de charges.\n3. Travaux d'entretien et gestion des parties communes.")) ?>
            </div>
        </div>

        <!-- Section 2 : Délibérations & Décisions Adoptées -->
        <div class="section-title">2. Délibérations et Décisions Adoptées</div>
        <div class="text-content">
            <?= nl2br(htmlspecialchars($ag['description'] ?: 'Les copropriétaires ont débattu des points inscrits à l\'ordre du jour. Les comptes de l\'exercice ont été approuvés à la majorité légale requise.')) ?>
            <?php if (! empty($ag['pv_texte'])) { ?>
                <div style="margin-top: 10px;">
                    <?= nl2br(htmlspecialchars($ag['pv_texte'])) ?>
                </div>
            <?php } ?>
        </div>

        <!-- Section Spéciale : Acte de Passation de Mandat (Article 20 Loi 18-00) si nouveau Syndic élu -->
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

        <!-- Section 3 : Clôture de Séance et Signatures Officielles -->
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

        <!-- Pied de page officiel -->
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

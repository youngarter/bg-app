<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Téléversement du Logo & Personnalisation Graphique
 * ==============================================================================
 * Ce script traite le téléversement et l'assignation du logo de la copropriété :
 *
 * Traitements :
 * 1. Réinitialisation : Efface le logo personnalisé pour rétablir les armoiries SVG par défaut.
 * 2. Téléversement Fichier :
 *    - Valide les extensions autorisées (PNG, JPG, JPEG, SVG, WEBP).
 *    - Limite la taille à 5 Mo pour préserver la mémoire du serveur.
 *    - Stocke l'image dans uploads/logos/ avec nommage horodaté unique.
 *    - Effectue la copie miroir vers le répertoire workspace de développement si présent.
 * 3. Attribution URL / Preset : Enregistre un chemin relatif ou lien absolu vers le logo.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Sécurisation et contrôle du mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings');
    exit;
}

// Seul le syndic en exercice peut modifier le branding officiel de la résidence
if (($user['role'] ?? '') !== 'syndic') {
    exit("Action réservée à l'administrateur Syndic.");
}

// 1. Réinitialisation au logo / armoiries SVG par défaut
if (! empty($_POST['reset_logo'])) {
    TenantDB::updateResidenceLogo(null);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&msg=logo_reset');
    exit;
}

// 2. Traitement d'un fichier image téléversé
if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['logo_file'];
    $allowedExts = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Contrôle d'extension de sécurité
    if (! in_array($ext, $allowedExts, true)) {
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&error='.urlencode("Format d'image non supporté. Formats acceptés : PNG, JPG, SVG, WEBP."));
        exit;
    }

    // Contrôle de taille maximale (5 Mo)
    if ($file['size'] > 5 * 1024 * 1024) {
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&error='.urlencode('Le fichier dépasse la taille maximale autorisée (5 Mo).'));
        exit;
    }

    $uploadDir = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'logos'.DIRECTORY_SEPARATOR;
    if (! is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = 'logo_'.substr($guid, 0, 8).'_'.time().'.'.$ext;
    $targetPath = $uploadDir.$filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Enregistrement du chemin relatif web
        $relativeUrl = 'uploads/logos/'.$filename;
        TenantDB::updateResidenceLogo($relativeUrl);

        // Copie miroir vers workspace de développement si présent
        $workspaceDir = 'c:'.DIRECTORY_SEPARATOR.'Users'.DIRECTORY_SEPARATOR.'ZetaAdmin'.DIRECTORY_SEPARATOR.'syndic'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'logos'.DIRECTORY_SEPARATOR;
        if (is_dir($workspaceDir)) {
            @copy($targetPath, $workspaceDir.$filename);
        }

        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&msg=logo_saved');
        exit;
    } else {
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&error='.urlencode("Erreur lors de l'enregistrement du fichier sur le serveur."));
        exit;
    }
}

// 3. Traitement d'une URL de logo ou d'un preset vectoriel
if (! empty($_POST['logo_url'])) {
    $logoUrl = trim((string) $_POST['logo_url']);
    TenantDB::updateResidenceLogo($logoUrl);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&msg=logo_saved');
    exit;
}

header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings');
exit;

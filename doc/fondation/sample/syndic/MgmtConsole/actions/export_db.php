<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Exportation d'une Base SQLite Dédiée (Console Master)
 * ==============================================================================
 * Ce script permet au Super-Administrateur de télécharger le fichier SQLite d'un tenant :
 *
 * Traitement :
 * 1. Exige le profil Super-Admin actif via requireSuperAdmin().
 * 2. Vérifie l'existence de la copropriété et de son fichier physique .sqlite.
 * 3. Émet les en-têtes HTTP de flux binaire (application/octet-stream).
 * 4. Transmet le fichier directement en téléchargement sécurisé.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/master_db.php';

// Étape 1 : Contrôle des privilèges Super-Administrateur
requireSuperAdmin();

// Étape 2 : Résolution du tenant et validation de l'existence physique
$id = (string) ($_GET['id'] ?? '');
$tenant = MasterDB::getTenantById($id);

if (! $tenant) {
    exit('Copropriété introuvable.');
}

$dbPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'tenants'.DIRECTORY_SEPARATOR.$id.'.sqlite';

if (! file_exists($dbPath)) {
    exit('Fichier de base de données introuvable.');
}

// Étape 3 : Diffusion du flux de téléchargement binaire
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$tenant['slug'].'-'.$id.'.sqlite"');
header('Content-Length: '.filesize($dbPath));
readfile($dbPath);
exit;

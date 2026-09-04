<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Sauvegarde Globale de la Flotte en ZIP (Console Master)
 * ==============================================================================
 * Ce script génère une archive ZIP complète de l'ensemble des bases SQLite de la plateforme :
 *
 * Traitement :
 * 1. Exige le profil Super-Admin actif via requireSuperAdmin().
 * 2. Crée une archive ZIP temporaire via la classe native ZipArchive.
 * 3. Intègre le registre maître data/master.sqlite.
 * 4. Parcourt et compresse l'ensemble des bases individuelles dans data/tenants/*.sqlite.
 * 5. Diffuse l'archive au navigateur avec horodatage et supprime le fichier temporaire.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/master_db.php';

// Étape 1 : Contrôle des privilèges Super-Administrateur
requireSuperAdmin();

$dataDir = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data';
$zip = new ZipArchive;
$tempZip = tempnam(sys_get_temp_dir(), 'syndic_backup_').'.zip';

// Étape 2 : Création de l'archive ZIP
if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("Impossible de générer l'archive ZIP.");
}

// 2.1 Ajout de la base maître
$masterFile = $dataDir.DIRECTORY_SEPARATOR.'master.sqlite';
if (file_exists($masterFile)) {
    $zip->addFile($masterFile, 'master.sqlite');
}

// 2.2 Ajout de l'ensemble des bases des copropriétés
$tenantsDir = $dataDir.DIRECTORY_SEPARATOR.'tenants';
if (is_dir($tenantsDir)) {
    $files = scandir($tenantsDir);
    foreach ($files as $f) {
        if (str_ends_with($f, '.sqlite')) {
            $zip->addFile($tenantsDir.DIRECTORY_SEPARATOR.$f, 'tenants/'.$f);
        }
    }
}

$zip->close();

// Étape 3 : Diffusion du flux de téléchargement au navigateur
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="SyndicPro_Backup_'.date('Y-m-d_His').'.zip"');
header('Content-Length: '.filesize($tempZip));
readfile($tempZip);

// Étape 4 : Suppression du fichier temporaire
@unlink($tempZip);
exit;

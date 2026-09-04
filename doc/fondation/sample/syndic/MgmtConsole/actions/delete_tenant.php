<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Suppression Définitive d'une Copropriété (Console Master)
 * ==============================================================================
 * Ce script traite la destruction irréversible d'une copropriété et de ses données :
 *
 * Sécurité & Confirmation :
 * 1. Exige le profil Super-Admin actif via requireSuperAdmin().
 * 2. Vérification de la méthode POST.
 * 3. Mot de passe de confirmation textuel obligatoire ('DELETE').
 * 4. Destruction physique du fichier data/tenants/{guid}.sqlite.
 * 5. Suppression de l'enregistrement dans master.sqlite et consignation dans l'audit.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/master_db.php';

// Étape 1 : Contrôle des privilèges Super-Administrateur
requireSuperAdmin();

// Étape 2 : Rejet des requêtes autres que POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$id = (string) ($_POST['id'] ?? '');
$confirm = (string) ($_POST['confirm'] ?? '');

// Étape 3 : Validation de la confirmation textuelle
if (empty($id) || $confirm !== 'DELETE') {
    header('Location: ../index.php?error='.urlencode('Confirmation requise pour la suppression définitive (tapez DELETE).'));
    exit;
}

// Étape 4 : Exécution de la suppression
try {
    $success = MasterDB::deleteTenant($id);
    if ($success) {
        header('Location: ../index.php?msg='.urlencode('La copropriété et sa base SQLite dédiée ont été supprimées définitivement.'));
    } else {
        header('Location: ../index.php?error='.urlencode('Copropriété introuvable.'));
    }
    exit;
} catch (Throwable $e) {
    header('Location: ../index.php?error='.urlencode('Erreur lors de la suppression : '.$e->getMessage()));
    exit;
}

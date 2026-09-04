<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Provisioning d'une Copropriété (Console Master)
 * ==============================================================================
 * Ce script traite la création ex-nihilo d'une nouvelle copropriété :
 *
 * Sécurité & Habilitation :
 * 1. Exige le profil Super-Admin actif via requireSuperAdmin().
 * 2. Vérification de la méthode POST.
 *
 * Traitement Métier :
 * 1. Valide les champs obligatoires (nom, syndic, email).
 * 2. Invoque MasterDB::provisionTenant($_POST) :
 *    - Génération du GUID v4.
 *    - Inscription dans master.sqlite.
 *    - Création physique de la base SQLite vierge dans data/tenants/{guid}.sqlite.
 *    - Création unique du compte administrateur du syndic.
 * 3. Consigne les informations de première connexion en session pour affichage du modal.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/master_db.php';

// Étape 1 : Vérification des privilèges Super-Administrateur
requireSuperAdmin();

// Étape 2 : Rejet des requêtes autres que POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Étape 3 : Nettoyage des paramètres requis
$nom = trim((string) ($_POST['nom'] ?? ''));
$ville = trim((string) ($_POST['ville'] ?? 'Casablanca'));
$nomSyndic = trim((string) ($_POST['nom_syndic'] ?? ''));
$emailSyndic = trim((string) ($_POST['email_syndic'] ?? ''));
$passwordSyndic = trim((string) ($_POST['password_syndic'] ?? 'syndic2026'));

if (empty($nom) || empty($nomSyndic) || empty($emailSyndic)) {
    header('Location: ../index.php?error='.urlencode('Le nom de la copropriété, le syndic et son email sont obligatoires.'));
    exit;
}

// Étape 4 : Provisioning physique et enregistrement
try {
    $tenant = MasterDB::provisionTenant($_POST);
    $_SESSION['new_tenant_created'] = [
        'id' => $tenant['id'],
        'nom' => $tenant['nom'],
        'email' => $emailSyndic,
        'password' => $passwordSyndic,
        'syndicLoginUrl' => $tenant['syndicLoginUrl'],
    ];
    header('Location: ../index.php?success=provisioned');
    exit;
} catch (Throwable $e) {
    header('Location: ../index.php?error='.urlencode($e->getMessage()));
    exit;
}

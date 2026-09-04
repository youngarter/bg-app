<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Dépôt d'un Ticket de Réclamation (Espace Syndic)
 * ==============================================================================
 * Ce script traite la création d'un incident technique ou réclamation :
 *
 * Sécurité & Suivi :
 * 1. Authentification vérifiée via requireAuth().
 * 2. Vérification des droits d'écriture via TenantDB::checkWritePermission().
 * 3. Affectation automatique de l'auteur d'après la session de l'utilisateur.
 * 4. Redirection vers la liste des réclamations avec notification.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Contrôle d'accès et interdiction de mutation en mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Traitement de la réclamation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['auteur'] = $user['nom'] ?? 'Syndic';
    TenantDB::addReclamation($_POST);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=reclamations&msg=reclamation_added');
    exit;
}

// Redirection de sécurité
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=reclamations');
exit;

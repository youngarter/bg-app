<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Déclaration d'Incident par un Résident (MgmtResident)
 * ==============================================================================
 * Ce script traite l'ouverture d'un ticket de réclamation technique par un copropriétaire :
 *
 * Sécurité & Droits :
 * 1. Authentification résidente vérifiée via requireResidentAuth().
 * 2. Contrôle de sécurité strict : interdiction d'écriture si la copropriété est en mode
 *    lecture seule (licence échue ou défaut d'abonnement).
 * 3. Attribution automatique de l'identité du déclarant d'après le compte authentifié.
 * 4. Enregistrement dans la table 'reclamations' et redirection vers le suivi des tickets.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/MgmtResidence/includes/tenant_db.php';
require_once __DIR__.'/../includes/resident_auth.php';

// Étape 1 : Résolution du GUID et contrôle d'authentification résident
$guid = TenantDB::resolveGuid();
$user = requireResidentAuth();

// Étape 2 : Contrôle de sécurité strict : interdire l'écriture en mode lecture seule
TenantDB::checkWritePermission();

// Étape 3 : Traitement de la réclamation soumise
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Attribution du nom de l'auteur depuis la session sécurisée
    $_POST['auteur'] = $user['nom'] ?? 'Résident';
    TenantDB::addReclamation($_POST);

    header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php?page=reclamations&success=reclamation_added');
    exit;
}

// Redirection de repli
header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php?page=reclamations');
exit;

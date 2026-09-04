<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Enregistrement d'un Copropriétaire & Compte Résident
 * ==============================================================================
 * Ce script traite la création d'une nouvelle fiche de copropriétaire :
 *
 * Sécurité & Automatismes :
 * 1. Authentification vérifiée via requireAuth().
 * 2. Vérification d'écriture (mode lecture seule bloqué).
 * 3. Création automatique de la fiche nominative dans 'coproprietaires'.
 * 4. Génération automatique de l'identifiant d'accès universel (prenom.nom@tag)
 *    et du compte utilisateur 'resident' dans la table 'users'.
 * 5. Redirection vers l'annuaire des copropriétaires.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Contrôle d'authentification et permissions d'écriture
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Traitement de la création
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TenantDB::addCoproprietaire($_POST);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=coproprietaires&msg=coproprietaire_created');
    exit;
}

// Redirection de sécurité
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=coproprietaires');
exit;

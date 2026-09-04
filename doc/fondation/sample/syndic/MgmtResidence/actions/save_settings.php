<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Mise à Jour des Paramètres de Copropriété
 * ==============================================================================
 * Ce script traite la modification des données signalétiques de la copropriété :
 *
 * Traitements :
 * 1. Authentification vérifiée via requireAuth().
 * 2. Vérification des droits d'écriture via TenantDB::checkWritePermission().
 * 3. Mise à jour des coordonnées : nom, adresse, ville, RIB bancaire, banque,
 *    titre foncier mère, nom et coordonnées du syndic en exercice.
 * 4. Redirection vers la page de configuration avec indicateur de sauvegarde.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Contrôle d'accès et interdiction de mutation en mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Traitement de la sauvegarde POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TenantDB::updateResidence($_POST);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=settings&saved=1');
    exit;
}

// Redirection de sécurité
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
exit;

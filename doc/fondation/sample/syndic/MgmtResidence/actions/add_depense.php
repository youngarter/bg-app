<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Comptabilisation d'une Dépense d'Exploitation
 * ==============================================================================
 * Ce script traite l'enregistrement d'une facture de prestataire ou charge d'immeuble :
 *
 * Sécurité & Comptabilité :
 * 1. Authentification vérifiée via requireAuth().
 * 2. Vérification des droits d'écriture via TenantDB::checkWritePermission().
 * 3. Ventilation par rubrique budgétaire (Gardiennage, Nettoyage, Électricité, Ascenseur...).
 * 4. Imputation à l'exercice comptable déduit de la date de valeur de la dépense.
 * 5. Redirection vers le livre journal des dépenses.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Contrôle d'accès et interdiction de mutation en mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Traitement de la dépense POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TenantDB::addDepense($_POST);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=depenses&msg=depense_created');
    exit;
}

// Redirection de sécurité
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=depenses');
exit;

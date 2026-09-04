<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Enregistrement d'un Encaissement & Quittance
 * ==============================================================================
 * Ce script traite l'enregistrement comptable d'un règlement de copropriétaire :
 *
 * Sécurité & Conformité Comptable (Loi 18-00) :
 * 1. Authentification vérifiée via requireAuth().
 * 2. Vérification des droits d'écriture (mode lecture seule bloqué).
 * 3. Génération d'un numéro officiel de quittance libératoire (QUITT-YYYY-HEX).
 * 4. Imputation du règlement aux charges courantes ou fonds de travaux.
 * 5. Redirection vers le registre des paiements avec message de succès.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Contrôle d'accès et interdiction de mutation en mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Traitement de l'encaissement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TenantDB::addPaiement($_POST);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=paiements&msg=paiement_created');
    exit;
}

// Redirection de sécurité
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=paiements');
exit;

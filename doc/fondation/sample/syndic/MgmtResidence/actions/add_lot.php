<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Enregistrement d'un Lot Cadastral
 * ==============================================================================
 * Ce script traite l'ajout d'un lot privatif au registre cadastral de la copropriété :
 *
 * Sécurité & Contrôle :
 * 1. Authentification requise (Syndic ou Délégué habilité).
 * 2. Contrôle du mode lecture seule via TenantDB::checkWritePermission().
 * 3. Enregistrement des tantièmes indivis (base 10 000).
 * 4. Redirection vers la page du cadastre des lots avec notification.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Vérification d'authentification et contrôle d'écriture
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Traitement de la mutation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    TenantDB::addLot($_POST);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=lots&msg=lot_created');
    exit;
}

// Redirection de repli
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=lots');
exit;

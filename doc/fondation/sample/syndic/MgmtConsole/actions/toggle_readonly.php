<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Verrouillage / Déverrouillage Lecture Seule
 * ==============================================================================
 * Ce script permet au Super-Administrateur de basculer une copropriété en lecture seule :
 *
 * Traitement :
 * 1. Exige le profil Super-Admin actif via requireSuperAdmin().
 * 2. Active ou désactive le flag 'faulty_payment_lock' dans master.sqlite.
 * 3. Consigne le motif du verrouillage et notifie le journal d'audit système.
 * 4. Redirige vers le dashboard de la console avec message de confirmation.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/master_db.php';

// Étape 1 : Contrôle d'habilitation Super-Admin
requireSuperAdmin();

// Étape 2 : Récupération des paramètres
$id = (string) ($_POST['id'] ?? ($_GET['id'] ?? ''));
$locked = isset($_POST['locked']) ? (bool) $_POST['locked'] : (isset($_GET['locked']) ? (bool) $_GET['locked'] : true);
$reason = (string) ($_POST['reason'] ?? ($locked ? 'Défaut de règlement de l\'abonnement plateforme' : ''));

// Étape 3 : Application de la bascule
if (! empty($id)) {
    MasterDB::toggleReadOnly($id, $locked, $reason);
    $msg = $locked ? 'Copropriété verrouillée en lecture seule.' : 'Copropriété déverrouillée avec succès.';
    header('Location: ../index.php?msg='.urlencode($msg));
    exit;
}

header('Location: ../index.php');
exit;

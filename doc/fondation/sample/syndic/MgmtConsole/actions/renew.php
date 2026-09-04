<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Renouvellement de Licence (Console Master)
 * ==============================================================================
 * Ce script traite la prolongation de la licence d'une copropriété :
 *
 * Processus :
 * 1. Exige le profil Super-Admin actif via requireSuperAdmin().
 * 2. Récupère le GUID du tenant et la durée à ajouter (6 ou 12 mois).
 * 3. Prolonge la date d'expiration et la période de grâce dans master.sqlite.
 * 4. Déverrouille automatiquement le mode lecture seule si actif.
 * 5. Consigne l'opération dans le journal d'audit et redirige avec confirmation.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/master_db.php';

// Étape 1 : Contrôle des privilèges Super-Administrateur
requireSuperAdmin();

// Étape 2 : Récupération des paramètres
$id = (string) ($_POST['id'] ?? ($_GET['id'] ?? ''));
$months = (int) ($_POST['months'] ?? ($_GET['months'] ?? 12));

// Étape 3 : Exécution du renouvellement
if (! empty($id)) {
    MasterDB::renewLicense($id, $months);
    header('Location: ../index.php?msg='.urlencode("Licence prolongée de +$months mois avec succès."));
    exit;
}

header('Location: ../index.php');
exit;

<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Émission d'un Appel de Fonds (Appels de Charges)
 * ==============================================================================
 * Ce script traite la soumission du formulaire d'émission d'appel de fonds :
 *
 * Sécurité & Prérequis :
 * 1. Authentification obligatoire via requireAuth().
 * 2. Vérification des droits d'écriture via TenantDB::checkWritePermission()
 *    (rejet immédiat si la copropriété est verrouillée en mode Lecture Seule).
 * 3. Validation de la méthode HTTP (POST exclusivement).
 *
 * Traitement Métier (Loi 18-00) :
 * 1. Contrôle du montant global appelé (doit être strictement supérieur à 0 DH).
 * 2. Génération d'un numéro d'appel séquentiel horodaté (APP-YYYY-NN).
 * 3. Enregistrement dans la table 'appels_fonds' avec le statut 'exigible'.
 * 4. Redirection vers la liste des appels avec message de confirmation.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Sécurisation de l'accès et vérification du mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Rejet des requêtes autres que POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=appels');
    exit;
}

// Étape 3 : Récupération et assainissement des paramètres du formulaire
$exercice = (int) ($_POST['exercice'] ?? date('Y'));
$montantTotal = (float) ($_POST['montant_total'] ?? 0);
$type = (string) ($_POST['type'] ?? 'charges_courantes');
$periode = trim((string) ($_POST['periode'] ?? ''));
$dateExigibilite = (string) ($_POST['date_exigibilite'] ?? date('Y-m-d'));
$description = trim((string) ($_POST['description'] ?? ''));

// Étape 4 : Validation des contraintes budgétaires
if ($montantTotal <= 0) {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=appels&error='.urlencode("Le montant total de l'appel de fonds doit être supérieur à zéro."));
    exit;
}

// Libellé de période par défaut si non précisé
if (empty($periode)) {
    $periode = 'Appel de fonds ('.$exercice.')';
}

// Construction du payload de données pour la base de données
$data = [
    'exercice' => $exercice,
    'type' => $type,
    'periode' => $periode,
    'date_exigibilite' => $dateExigibilite,
    'montant_total' => $montantTotal,
    'description' => $description,
    'statut' => 'exigible',
];

// Étape 5 : Persistance dans la base dédiée du tenant
try {
    $appelId = TenantDB::addAppel($data);
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=appels&msg=appel_created&exercice='.$exercice);
    exit;
} catch (Throwable $e) {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=appels&error='.urlencode($e->getMessage()));
    exit;
}

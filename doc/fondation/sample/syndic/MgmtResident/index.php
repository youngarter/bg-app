<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Front Controller Principal de l'Espace Copropriétaires
 * ==============================================================================
 * Ce script orchestre la navigation et l'affichage du portail résident (MgmtResident) :
 *
 * Rôles & Flux d'Exécution :
 * 1. Authentification Résident :
 *    - Vérifie la session active et résout le GUID de la copropriété.
 *    - Réoriente immédiatement les profils syndics vers MgmtResidence.
 * 2. Chargement du Contexte :
 *    - Charge les métadonnées de licence, la fiche de copropriété et l'exercice sélectionné.
 * 3. Contrôle de Routage & Liste Blanche :
 *    - Filtre les pages accessibles aux copropriétaires (dashboard, paiements, réclamations,
 *      assemblées, projets, carnet, immeuble).
 * 4. Assemblage du Layout :
 *    - En-tête avec badge d'accès au bureau syndical pour les délégués, vue de page, et footer.
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/resident_auth.php';
require_once dirname(__DIR__).'/MgmtResidence/includes/tenant_db.php';
require_once __DIR__.'/includes/resident_db.php';

// Étape 1 : Résolution de la copropriété et authentification obligatoire du résident
$guid = TenantDB::resolveGuid();
$user = requireResidentAuth();

// Étape 2 : Sécurité supplémentaire : réorienter les administrateurs vers le cockpit syndic
if (($user['role'] ?? '') === 'syndic') {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
    exit;
}

// Étape 3 : Chargement des métadonnées de la copropriété
$meta = TenantDB::getTenantMeta();
$residence = TenantDB::getResidence();

// Étape 4 : Détermination de l'exercice fiscal et de la page sollicitée
$selectedExercice = (int) ($_GET['exercice'] ?? date('Y'));
$page = (string) ($_GET['page'] ?? 'dashboard');

// Étape 5 : Liste blanche stricte des vues autorisées dans l'espace résident
$allowedPages = [
    'dashboard' => 'dashboard.php',
    'paiements' => 'paiements.php',
    'reclamations' => 'reclamations.php',
    'assemblees' => 'assemblees.php',
    'projets' => 'projets.php',
    'carnet' => 'carnet.php',
    'immeuble' => 'immeuble.php',
];

if (! isset($allowedPages[$page])) {
    $page = 'dashboard';
}

// Étape 6 : Assemblage du Layout : En-tête HTML & Navigation latérale
require_once __DIR__.'/includes/header.php';

// Étape 7 : Inclusion du contrôleur de vue demandé
$pageFile = __DIR__.'/pages/'.$allowedPages[$page];
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    echo "<div class='p-8 text-center text-slate-500'>Module en cours de déploiement.</div>";
}

// Étape 8 : Pied de page
require_once __DIR__.'/includes/footer.php';

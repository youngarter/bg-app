<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Procès-Verbal d'AG, Budget Voté & Passation (Art. 20)
 * ==============================================================================
 * Ce script traite l'enregistrement officiel d'une délibération d'Assemblée Générale :
 *
 * Sécurité & Conformité Juridique (Loi 18-00) :
 * 1. Authentification vérifiée via requireAuth().
 * 2. Vérification des droits d'écriture via TenantDB::checkWritePermission().
 * 3. Enregistrement du budget prévisionnel annuel voté et ventilation sur les 8 rubriques.
 * 4. Passation de mandat (Art. 20) : si un nouveau syndic est élu, création automatique
 *    du compte administrateur, mise à jour des fiches et journalisation d'audit.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Contrôle d'accès et interdiction de mutation en mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Rejet des requêtes non POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=assemblees');
    exit;
}

// Étape 3 : Récupération des paramètres de séance
$type = (string) ($_POST['type'] ?? 'ordinaire');
$date = (string) ($_POST['date'] ?? date('Y-m-d'));
$lieu = trim((string) ($_POST['lieu'] ?? 'Hall principal de la résidence'));
$changement = ! empty($_POST['changement_syndic']) ? 1 : 0;

// Construction du tableau de données de la délibération
$data = [
    'type' => $type,
    'date' => $date,
    'lieu' => $lieu,
    'description' => trim((string) ($_POST['description'] ?? ('Assemblée Générale '.($type === 'extraordinaire' ? 'Extraordinaire' : 'Ordinaire')))),
    'ordre_du_jour' => trim((string) ($_POST['ordre_du_jour'] ?? '')),
    'tantiemes_presents' => (int) ($_POST['tantiemes_presents'] ?? 8500),
    'president_seance' => trim((string) ($_POST['president_seance'] ?? 'Président de séance élu')),
    'secretaire_seance' => trim((string) ($_POST['secretaire_seance'] ?? 'Secrétaire de séance élu')),
    'pv_texte' => trim((string) ($_POST['pv_texte'] ?? '')),
    'changement_syndic' => $changement,
    'nouveau_syndic_nom' => trim((string) ($_POST['nouveau_syndic_nom'] ?? '')),
    'nouveau_syndic_email' => trim((string) ($_POST['nouveau_syndic_email'] ?? '')),
    'nouveau_syndic_tel' => trim((string) ($_POST['nouveau_syndic_tel'] ?? '')),
    'nouveau_syndic_password' => trim((string) ($_POST['nouveau_syndic_password'] ?? 'syndic2026')),
    'date_effet_mandat' => (string) ($_POST['date_effet_mandat'] ?? $date),
    'tresorerie_arretee' => (float) ($_POST['tresorerie_arretee'] ?? 0),
    'exercice' => (int) ($_POST['exercice'] ?? date('Y', strtotime($date))),
    'budget_annuel_vote' => (float) ($_POST['budget_annuel_vote'] ?? 0),
    'frequence_appels' => trim((string) ($_POST['frequence_appels'] ?? 'trimestrielle')),
    'budget_rubriques' => is_array($_POST['budget_rubriques'] ?? null) ? $_POST['budget_rubriques'] : [],
];

// Étape 4 : Exécution de l'enregistrement et redirection
try {
    $agId = TenantDB::addAssemblee($data);
    $msg = $changement ? 'passation_success' : 'ag_created';
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=assemblees&msg='.$msg.'&ag_id='.urlencode($agId));
    exit;
} catch (Throwable $e) {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=assemblees&error='.urlencode($e->getMessage()));
    exit;
}

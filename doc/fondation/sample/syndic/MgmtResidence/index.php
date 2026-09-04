<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Front Controller Principal de l'Application Syndic
 * ==============================================================================
 * Ce script orchestre la navigation et l'affichage de l'Espace Syndic (MgmtResidence) :
 *
 * Rôles & Flux d'Exécution :
 * 1. Authentification & Filtrage :
 *    - Vérifie la session active et résout le GUID de la copropriété.
 *    - Rejette les résidents non délégués vers MgmtResident.
 * 2. Contrôle d'Accès Granulaire par Rôle (RBAC Délégués) :
 *    - Valide que le membre délégué dispose des droits requis pour le module sollicité.
 *    - Redirige automatiquement vers sa première page autorisée en cas d'accès non permis.
 * 3. Injection du Layout Unifié :
 *    - Charge l'en-tête HTML, la barre de navigation latérale (sidebar.php),
 *      le contrôleur de page sollicité (pages/*.php), puis le pied de page (footer.php).
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/tenant_auth.php';
require_once __DIR__.'/includes/tenant_db.php';

// Étape 1 : Résolution de la copropriété et contrôle d'authentification
$guid = TenantDB::resolveGuid();
$user = requireAuth();

// Étape 2 : Ségrégation d'accès : les simples résidents sont aiguillés vers leur portail
if (($user['role'] ?? '') === 'resident' && empty($user['delegate'])) {
    header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php');
    exit;
}

// Étape 3 : Chargement des métadonnées de licence et de la fiche de copropriété
$meta = TenantDB::getTenantMeta();
$residence = TenantDB::getResidence();

// Étape 4 : Détermination de l'exercice fiscal actif et du module demandé
$selectedExercice = (int) ($_GET['exercice'] ?? date('Y'));
$page = (string) ($_GET['page'] ?? 'dashboard');

// Étape 5 : Liste blanche exhaustive des modules opérationnels du syndic
$allowedPages = [
    'dashboard' => 'dashboard.php',
    'annexes' => 'annexes.php',
    'reclamations' => 'reclamations.php',
    'projets' => 'projets.php',
    'coproprietaires' => 'coproprietaires.php',
    'lots' => 'lots.php',
    'delegues' => 'delegues.php',
    'appels' => 'appels.php',
    'paiements' => 'paiements.php',
    'relances' => 'relances.php',
    'depenses' => 'depenses.php',
    'fournisseurs' => 'fournisseurs.php',
    'carnet' => 'carnet.php',
    'assemblees' => 'assemblees.php',
    'settings' => 'settings.php',
];

// Repli automatique sur le tableau de bord si le paramètre est inconnu
if (! isset($allowedPages[$page])) {
    $page = 'dashboard';
}

// Étape 6 : Contrôle strict des permissions pour les membres délégués du bureau
if (! empty($user['delegate'])) {
    $isAllowed = TenantDB::hasPermission($user, $page);
    if (! $isAllowed) {
        // Recherche de la première page autorisée dans le profil du délégué
        $perms = $user['delegate']['permissions_array'] ?? [];
        $fallbackPage = null;
        foreach ($allowedPages as $key => $file) {
            if (in_array($key, $perms, true)) {
                $fallbackPage = $key;
                break;
            }
        }

        // Redirection vers le premier module habilité ou blocage explicite
        if ($fallbackPage && $page !== $fallbackPage) {
            header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page='.urlencode($fallbackPage).'&error=unauthorized_module');
            exit;
        } else {
            exit("<div style='font-family:sans-serif; padding:40px; text-align:center; color:#e11d48;'>
                    <h2>Accès Non Autorisé</h2>
                    <p>Votre profil de délégué ne dispose pas des droits requis pour ce module.</p>
                    <p><a href='/Syndic/MgmtResident/".urlencode($guid)."/index.php'>Retourner à Mon Espace Résident &rarr;</a></p>
                 </div>");
        }
    }
}

// Étape 7 : Assemblage du Layout : En-tête HTML & Barre de navigation latérale
require_once __DIR__.'/includes/header.php';

// Étape 8 : Inclusion du contrôleur de page demandé
$pageFile = __DIR__.'/pages/'.$allowedPages[$page];
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    echo "<div class='p-8 text-center text-slate-500'>Module en cours de déploiement.</div>";
}

// Étape 9 : Pied de page et scripts globaux
require_once __DIR__.'/includes/footer.php';

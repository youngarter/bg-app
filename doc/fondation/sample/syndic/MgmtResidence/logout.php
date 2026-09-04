<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Déconnexion de la Copropriété (Cockpit Syndic)
 * ==============================================================================
 * Ce script assure la clôture sécurisée de la session syndic en cours :
 *
 * Processus :
 * 1. Mémorise le GUID de la copropriété pour permettre la reconnexion ciblée.
 * 2. Purge l'intégralité des variables de session ($_SESSION = []).
 * 3. Révoque le cookie 'syndic_residence_session' sur le navigateur client.
 * 4. Détruit l'état de session sur le serveur avec session_destroy().
 * 5. Redirige vers l'écran de login spécifique de la copropriété (/Syndic/MgmtResidence/[guid]/login.php).
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/tenant_auth.php';

// Étape 1 : Mémorisation du GUID actif avant destruction de la session
$guid = TenantDB::resolveGuid();

// Étape 2 : Purge complète des données de session en mémoire
$_SESSION = [];

// Étape 3 : Destruction du cookie de session côté client
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Étape 4 : Destruction du conteneur de session côté serveur
session_destroy();

// Étape 5 : Redirection ciblée vers la page de login de cette copropriété
header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/login.php');
exit;

<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Déconnexion Super-Administrateur (Console Master)
 * ==============================================================================
 * Ce script réalise la clôture étanche et sécurisée de la session Super-Admin :
 *
 * Processus :
 * 1. Vide le tableau des variables de session ($_SESSION = []).
 * 2. Révoque le cookie de session 'syndic_master_session' sur le navigateur client.
 * 3. Détruit complètement l'état de session sur le serveur avec session_destroy().
 * 4. Redirige l'administrateur vers l'écran de connexion login.php.
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/includes/auth.php';

// 1. Réinitialisation des variables de session en mémoire
$_SESSION = [];

// 2. Destruction du cookie de session côté navigateur si utilisé
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

// 3. Destruction physique de la session serveur
session_destroy();

// 4. Redirection vers le formulaire de connexion
header('Location: login.php');
exit;

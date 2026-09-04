<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Middleware d'Authentification Super-Admin (Console Master)
 * ==============================================================================
 * Ce module protège l'accès à la console d'administration centrale de Bayan Gestion :
 *
 * 1. Isolation de Session :
 *    - Utilise le nom de cookie dédié 'syndic_master_session'.
 *    - Évite toute collision ou usurpation de contexte avec les sessions des copropriétés
 *      ('syndic_residence_session').
 *
 * 2. Contrôle d'Accès Strict :
 *    - Intercepte toute requête non authentifiée et redirige vers login.php.
 *    - Maintient l'état du super-administrateur connecté en session sécurisée.
 * ==============================================================================
 */

declare(strict_types=1);

// Démarrage de la session dédiée au contexte Super-Admin si non initialisée
if (session_status() === PHP_SESSION_NONE) {
    session_name('syndic_master_session');
    session_start();
}

/**
 * Gardien d'accès (Guard Middleware) : exige une authentification super-administrateur active.
 * Si l'utilisateur n'est pas connecté, interrompt le script et redirige vers login.php.
 *
 * @return array Profil complet du super-administrateur extrait de la session.
 */
function requireSuperAdmin(): array
{
    // Contrôle de présence de la clé 'super_admin' dans le magasin de session
    if (empty($_SESSION['super_admin'])) {
        header('Location: login.php');
        exit;
    }

    return $_SESSION['super_admin'];
}

/**
 * Récupère le profil du super-administrateur connecté sans forcer la redirection.
 *
 * @return array|null Données de session de l'administrateur ou null si anonyme.
 */
function getCurrentSuperAdmin(): ?array
{
    return $_SESSION['super_admin'] ?? null;
}

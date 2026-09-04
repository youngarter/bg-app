<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Déconnexion de l'Espace Résident (MgmtResident)
 * ==============================================================================
 * Ce script réalise la clôture de session du copropriétaire :
 *
 * Processus :
 * 1. Initialise la session 'syndic_residence_session' si nécessaire.
 * 2. Mémorise le GUID du tenant pour permettre une reconnexion sur la même résidence.
 * 3. Supprime les clés d'identification de l'utilisateur ('tenant_user', 'tenant_guid').
 * 4. Redirige vers l'écran de login spécifique du résident ou vers l'accueil général.
 * ==============================================================================
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('syndic_residence_session');
    session_start();
}

// 1. Récupération du GUID du tenant pour ciblage de la redirection
$tenant = $_GET['tenant'] ?? ($_SESSION['tenant_guid'] ?? '');

// 2. Révocation des variables d'authentification résident
unset($_SESSION['tenant_user']);
unset($_SESSION['tenant_guid']);

// 3. Redirection vers le formulaire de connexion de la copropriété
if (! empty($tenant)) {
    header('Location: /Syndic/MgmtResident/'.urlencode($tenant).'/login.php');
} else {
    header('Location: /Syndic/index.php');
}
exit;

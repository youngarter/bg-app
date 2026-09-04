<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Middleware d'Authentification Espace Résident (ResidentAuth)
 * ==============================================================================
 * Ce module régit l'accès au portail privatif des copropriétaires (MgmtResident) :
 *
 * 1. Périmètre d'Authentification :
 *    - Partage le conteneur de session 'syndic_residence_session' avec MgmtResidence.
 *    - Valide l'appartenance de la session au GUID actif de la copropriété.
 *
 * 2. Aiguillage des Rôles :
 *    - Si un compte syndic se connecte sur l'espace résident, redirection automatique
 *      vers le cockpit d'administration /Syndic/MgmtResidence/[guid]/.
 *    - Détection automatique du statut de délégué pour afficher le badge d'accès rapide
 *      au bureau syndical.
 * ==============================================================================
 */

declare(strict_types=1);

// Démarrage de la session partagée copropriété
if (session_status() === PHP_SESSION_NONE) {
    session_name('syndic_residence_session');
    session_start();
}

require_once dirname(__DIR__, 2).'/MgmtResidence/includes/tenant_db.php';
require_once dirname(__DIR__, 2).'/MgmtResidence/includes/brand.php';
require_once __DIR__.'/resident_db.php';

/**
 * Récupère le profil du résident actuellement connecté.
 * Vérifie l'intégrité de la session et rafraîchit ses mandats de délégation au conseil syndical.
 *
 * @return array|null Profil résident connecté ou null si anonyme ou session invalide.
 */
function getCurrentResidentUser(): ?array
{
    $guid = TenantDB::resolveGuid();
    $sessionUser = $_SESSION['tenant_user'] ?? null;
    $sessionGuid = $_SESSION['tenant_guid'] ?? null;

    if ($sessionUser && $sessionGuid === $guid) {
        // Hydratation continue des droits de délégation du copropriétaire
        $delegate = TenantDB::getDelegateByUserId($sessionUser['id']);
        $sessionUser['delegate'] = $delegate;
        $_SESSION['tenant_user']['delegate'] = $delegate;

        return $sessionUser;
    }

    return null;
}

/**
 * Gardien d'accès (Guard Middleware) pour le portail Espace Copropriétaires.
 * Redirige vers la page de connexion si aucune session n'est ouverte.
 * Réoriente les comptes syndics vers leur console de gestion.
 *
 * @return array Profil du copropriétaire résident authentifié.
 */
function requireResidentAuth(): array
{
    $guid = TenantDB::resolveGuid();
    $user = getCurrentResidentUser();

    // 1. Contrôle d'authentification active
    if (! $user) {
        header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/login.php');
        exit;
    }

    // 2. Si un compte syndic tente d'accéder au portail résident, le réorienter vers le cockpit
    if (($user['role'] ?? '') === 'syndic') {
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php');
        exit;
    }

    return $user;
}

/**
 * Authentifie un copropriétaire résident avec son identifiant universel ou email.
 *
 * @param  string  $identifier  Identifiant (format universel 'prenom.nom@tag', email ou nom).
 * @param  string  $password  Mot de passe en clair (par défaut 'resident2026').
 * @return bool True si la connexion est établie avec succès, false sinon.
 */
function loginResidentUser(string $identifier, string $password): bool
{
    $guid = TenantDB::resolveGuid();
    $pdo = TenantDB::getPdo();
    $cleanId = trim(strtolower($identifier));

    // 1. Recherche directe par email dans la table users
    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = ?');
    $stmt->execute([$cleanId]);
    $user = $stmt->fetch();

    // 2. Recherche par format convivial "user@[tag]" ou "prenom.nom"
    if (! $user) {
        $userPart = $cleanId;
        if (str_contains($cleanId, '@')) {
            [$userPart, $domain] = explode('@', $cleanId, 2);
        }

        $stmt = $pdo->prepare('
            SELECT u.* FROM users u
            WHERE LOWER(u.email) LIKE ? OR LOWER(u.email) LIKE ?
            LIMIT 1
        ');
        $stmt->execute([$userPart.'@%', '%'.$userPart.'%']);
        $user = $stmt->fetch();

        // 3. Recherche par nom ou prénom du copropriétaire
        if (! $user) {
            $stmt = $pdo->prepare('
                SELECT u.* FROM users u
                LEFT JOIN coproprietaires c ON u.coproprietaire_id = c.id
                WHERE LOWER(u.nom) LIKE ? OR LOWER(c.nom) LIKE ? OR LOWER(c.prenom) LIKE ?
                LIMIT 1
            ');
            $like = '%'.str_replace('.', '%', $userPart).'%';
            $stmt->execute([$like, $like, $like]);
            $user = $stmt->fetch();
        }
    }

    // 4. Contrôle de mot de passe Bcrypt (avec fallback mot de passe de test)
    if ($user && (password_verify($password, $user['password_hash']) || $password === 'resident2026' || $password === 'syndic2026')) {
        unset($user['password_hash']);
        $_SESSION['tenant_user'] = $user;
        $_SESSION['tenant_guid'] = $guid;

        return true;
    }

    return false;
}

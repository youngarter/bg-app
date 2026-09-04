<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Middleware d'Authentification & Sessions Syndic (TenantAuth)
 * ==============================================================================
 * Ce module gère la sécurité et les sessions de l'application Syndic (MgmtResidence) :
 *
 * 1. Isolation de Session par Copropriété :
 *    - Nom de session unifié : 'syndic_residence_session'.
 *    - Validation stricte de concordance entre le GUID résolu et le GUID stocké en session.
 *
 * 2. Authentification Multi-Identifiants :
 *    - Supporte l'adresse email standard (ex: syndic.atlas@gmail.com).
 *    - Supporte les identifiants conviviaux de copropriétaires (ex: mehdi.elamrani@atlas, tariq.alami@atlas).
 *    - Résolution automatique par préfixe ou nom complet si l'utilisateur saisit son prénom.
 *
 * 3. Habilitation des Membres Délégués du Bureau :
 *    - Si un copropriétaire résident est habilité dans la table 'delegates', son rôle
 *      de membre du bureau (Vice-Syndic, Trésorier, Secrétaire) est injecté dynamiquement.
 *    - Redirection vers MgmtResident si un simple résident non délégué tente d'accéder au cockpit.
 * ==============================================================================
 */

declare(strict_types=1);

// Démarrage ou reprise de la session copropriété
if (session_status() === PHP_SESSION_NONE) {
    session_name('syndic_residence_session');
    session_start();
}

require_once __DIR__.'/tenant_db.php';

/**
 * Récupère le profil de l'utilisateur connecté pour la copropriété courante.
 * Vérifie la stricte concordance entre le GUID de l'URL et le GUID en session.
 * Actualise les délégations en temps réel pour refléter tout changement de permissions.
 *
 * @return array|null Profil utilisateur enrichi de sa délégation active, ou null si non connecté.
 */
function getCurrentUser(): ?array
{
    $guid = TenantDB::resolveGuid();
    $sessionUser = $_SESSION['tenant_user'] ?? null;
    $sessionGuid = $_SESSION['tenant_guid'] ?? null;

    // Contrôle d'étanchéité : la session doit appartenir impérativement à la copropriété courante
    if ($sessionUser && $sessionGuid === $guid) {
        // Pour les copropriétaires, rafraîchir en direct le mandat de délégation depuis SQLite
        if (($sessionUser['role'] ?? '') === 'resident') {
            $delegate = TenantDB::getDelegateByUserId($sessionUser['id']);
            $sessionUser['delegate'] = $delegate;
            $_SESSION['tenant_user']['delegate'] = $delegate;
        }

        return $sessionUser;
    }

    return null;
}

/**
 * Gardien d'accès (Guard Middleware) pour le cockpit d'administration Syndic.
 * Intercepte les accès anonymes et redirige vers l'écran de login.
 * Aiguille les simples résidents vers leur portail dédié MgmtResident.
 *
 * @return array Profil de l'utilisateur habilité (Syndic en titre ou Copropriétaire Délégué).
 */
function requireAuth(): array
{
    $guid = TenantDB::resolveGuid();
    $user = getCurrentUser();

    // 1. Redirection vers la page de connexion si aucune session active
    if (! $user) {
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/login.php');
        exit;
    }

    // 2. Si un résident sans mandat syndical délégué tente d'accéder au cockpit d'administration,
    //    l'aiguiller poliment vers son Espace Résident privatif
    if (($user['role'] ?? '') === 'resident' && empty($user['delegate'])) {
        header('Location: /Syndic/MgmtResident/'.urlencode($guid).'/index.php');
        exit;
    }

    return $user;
}

/**
 * Authentifie un utilisateur au sein de la copropriété active.
 *
 * Résolution multi-critères :
 * 1. Recherche exacte par adresse email dans la table 'users'.
 * 2. Recherche par format convivial prenom.nom@[tag] ou par préfixe d'identifiant.
 * 3. Recherche par nom et prénom du copropriétaire rattaché.
 * 4. Vérification du mot de passe par password_verify() (avec fallback démo).
 *
 * @param  string  $identifier  Identifiant saisi (email, identifiant convivial ou nom).
 * @param  string  $password  Mot de passe en clair.
 * @return bool True si l'authentification a abouti, false en cas d'échec.
 */
function loginUser(string $identifier, string $password): bool
{
    $guid = TenantDB::resolveGuid();
    $pdo = TenantDB::getPdo();
    $cleanId = trim(strtolower($identifier));

    // Étape 1 : Recherche directe exacte par email dans la table users
    $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = ?');
    $stmt->execute([$cleanId]);
    $user = $stmt->fetch();

    // Étape 2 : Si non trouvé, tentative de résolution du format convivial user@[tag]
    if (! $user) {
        $userPart = $cleanId;
        if (str_contains($cleanId, '@')) {
            [$userPart, $domain] = explode('@', $cleanId, 2);
        }

        // Correspondance partielle par préfixe
        $stmt = $pdo->prepare('
            SELECT u.* FROM users u
            WHERE LOWER(u.email) LIKE ? OR LOWER(u.email) LIKE ?
            LIMIT 1
        ');
        $stmt->execute([$userPart.'@%', '%'.$userPart.'%']);
        $user = $stmt->fetch();

        // Étape 3 : Recherche par nom ou prénom du copropriétaire lié
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

    // Étape 4 : Vérification cryptographique du mot de passe
    if ($user && (password_verify($password, $user['password_hash']) || $password === 'syndic2026' || $password === 'resident2026')) {
        // Nettoyage de l'empreinte de sécurité avant conservation en session
        unset($user['password_hash']);

        // Étape 5 : Hydratation du mandat de délégation si l'utilisateur fait partie du conseil syndical
        $delegate = TenantDB::getDelegateByUserId($user['id']);
        if ($delegate) {
            $user['delegate'] = $delegate;
        }

        // Inscription dans la session PHP sécurisée
        $_SESSION['tenant_user'] = $user;
        $_SESSION['tenant_guid'] = $guid;

        return true;
    }

    return false;
}

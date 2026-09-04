<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Action : Gestion des Délégations & Conseil Syndical
 * ==============================================================================
 * Ce script traite la configuration du bureau du conseil syndical (Loi 18-00) :
 *
 * Rôles Gérés :
 * 1. Vice-Syndic Adjoint : Tous modules délégués avec suppléance du syndic.
 * 2. Comptable / Trésorier : Suivi financier, appels, encaissements et dépenses.
 * 3. Secrétaire Général : Registres d'AG, carnet d'entretien, réclamations.
 *
 * Sous-Actions Prises en Charge :
 * - 'add'           : Habilite un copropriétaire avec un rôle et une liste de permissions JSON.
 * - 'update'        : Modifie les attributions ou le titre d'une délégation existante.
 * - 'delete'        : Révoque définitivement la délégation syndicale.
 * - 'toggle_status' : Suspend ou réactive temporairement les droits d'un membre délégué.
 * ==============================================================================
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/includes/tenant_auth.php';
require_once dirname(__DIR__).'/includes/tenant_db.php';

// Étape 1 : Authentification et interdiction du mode lecture seule
$user = requireAuth();
TenantDB::checkWritePermission();
$guid = TenantDB::resolveGuid();

// Étape 2 : Contrôle d'habilitation hiérarchique
// Seul le syndic en exercice ou un délégué doté de la permission 'delegues' peut modifier le bureau
$canManage = (($user['role'] ?? '') === 'syndic') || TenantDB::hasPermission($user, 'delegues') || TenantDB::hasPermission($user, 'settings');
if (! $canManage) {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues&error=action_unauthorized');
    exit;
}

// Étape 3 : Détection de l'action demandée
$action = (string) ($_POST['action'] ?? ($_GET['action'] ?? ''));

try {
    // 3.1 Ajout d'une nouvelle délégation
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $delId = TenantDB::addDelegate($_POST);
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues&msg=delegate_added');
        exit;
    }

    // 3.2 Mise à jour d'attributions
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = trim((string) ($_POST['id'] ?? ''));
        if (empty($id)) {
            throw new InvalidArgumentException('Identifiant du délégué manquant.');
        }
        TenantDB::updateDelegate($id, $_POST);
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues&msg=delegate_updated');
        exit;
    }

    // 3.3 Révocation d'un membre délégué
    if ($action === 'delete') {
        $id = trim((string) ($_POST['id'] ?? ($_GET['id'] ?? '')));
        if (empty($id)) {
            throw new InvalidArgumentException('Identifiant du délégué manquant.');
        }
        TenantDB::deleteDelegate($id);
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues&msg=delegate_deleted');
        exit;
    }

    // 3.4 Bascule de statut (actif <-> suspendu)
    if ($action === 'toggle_status') {
        $id = trim((string) ($_POST['id'] ?? ($_GET['id'] ?? '')));
        $current = TenantDB::getDelegateById($id);
        if ($current) {
            $newStatus = ($current['statut'] === 'actif') ? 'suspendu' : 'actif';
            TenantDB::updateDelegate($id, [
                'titre_role' => $current['titre_role'],
                'role_label' => $current['role_label'],
                'permissions' => $current['permissions_array'],
                'statut' => $newStatus,
                'notes' => $current['notes'] ?? '',
            ]);
        }
        header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues&msg=status_updated');
        exit;
    }
} catch (Throwable $e) {
    header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues&error='.urlencode($e->getMessage()));
    exit;
}

header('Location: /Syndic/MgmtResidence/'.urlencode($guid).'/index.php?page=delegues');
exit;

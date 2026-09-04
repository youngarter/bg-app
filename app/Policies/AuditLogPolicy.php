<?php

namespace App\Policies;

use App\Enums\ResidenceUserRole;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Matrice 02 §4: Journal d'audit
     * Admin: R | Gérant: R | Gestionnaire: — | Comptable: R | Conseil: R
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $residence = $auditLog->residence;
        if (! $residence) {
            return false;
        }

        $role = $user->roleInResidence($residence);

        // Matrice 02 §4: Le Gestionnaire n'a aucun droit (—) sur le journal d'audit
        return in_array($role, [
            ResidenceUserRole::Gerant,
            ResidenceUserRole::Comptable,
            ResidenceUserRole::PresidentConseil,
            ResidenceUserRole::MembreConseil,
        ], true);
    }

    public function create(User $user): bool
    {
        return false; // Journal système
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false; // INSERT ONLY
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false; // INSERT ONLY
    }
}

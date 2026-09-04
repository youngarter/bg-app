<?php

namespace App\Policies;

use App\Enums\ResidenceUserRole;
use App\Models\Residence;
use App\Models\User;

class ResidencePolicy
{
    /**
     * Matrice 02 §4: Résidence, licence
     * Admin: CRUD | Gérant: R | Gestionnaire: R | Comptable: R | Conseil: R
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Residence $residence): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $role = $user->roleInResidence($residence);

        return in_array($role, [
            ResidenceUserRole::Gerant,
            ResidenceUserRole::Gestionnaire,
            ResidenceUserRole::Comptable,
            ResidenceUserRole::PresidentConseil,
            ResidenceUserRole::MembreConseil,
        ], true);
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Residence $residence): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, Residence $residence): bool
    {
        return $user->isPlatformAdmin();
    }
}

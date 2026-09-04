<?php

namespace App\Policies;

use App\Enums\ResidenceUserRole;
use App\Models\ResidenceRole;
use App\Models\User;

class ResidenceRolePolicy
{
    public function view(User $user, ResidenceRole $role): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $residence = $role->residence;
        if (! $residence) {
            return false;
        }

        $userRole = $user->roleInResidence($residence);

        return in_array($userRole, [
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

    public function update(User $user, ResidenceRole $role): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, ResidenceRole $role): bool
    {
        return $user->isPlatformAdmin();
    }
}

<?php

namespace App\Policies;

use App\Enums\ResidenceUserRole;
use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    /**
     * Matrice 02 §4: Résidence, licence
     * Admin: CRUD | Gérant: R | Gestionnaire: R | Comptable: R | Conseil: R
     */
    public function view(User $user, License $license): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        $residence = $license->residence;
        if (! $residence) {
            return false;
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

    public function update(User $user, License $license): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, License $license): bool
    {
        return $user->isPlatformAdmin();
    }

    public function renew(User $user, License $license): bool
    {
        return $user->isPlatformAdmin();
    }

    public function suspend(User $user, License $license): bool
    {
        return $user->isPlatformAdmin();
    }

    public function reactivate(User $user, License $license): bool
    {
        return $user->isPlatformAdmin();
    }
}

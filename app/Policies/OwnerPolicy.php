<?php

namespace App\Policies;

use App\Enums\ResidenceUserRole;
use App\Models\Owner;
use App\Models\User;
use App\Support\ResidenceContext;

class OwnerPolicy
{
    /**
     * Matrice 02 §4 : Copropriétaires, détentions
     * Admin: R | Gérant: CRUD | Gestionnaire: CRUD | Comptable: R | Conseil: R
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Owner $owner): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        $residence = ResidenceContext::get();
        if (! $residence) {
            return false;
        }

        $role = $user->roleInResidence($residence);

        return in_array($role, [
            ResidenceUserRole::Gerant,
            ResidenceUserRole::Gestionnaire,
        ], true);
    }

    public function update(User $user, Owner $owner): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        $residence = ResidenceContext::get() ?? $owner->residence;
        if (! $residence) {
            return false;
        }

        $role = $user->roleInResidence($residence);

        return in_array($role, [
            ResidenceUserRole::Gerant,
            ResidenceUserRole::Gestionnaire,
        ], true);
    }
}

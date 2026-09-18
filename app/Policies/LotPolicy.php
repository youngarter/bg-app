<?php

namespace App\Policies;

use App\Enums\ResidenceUserRole;
use App\Models\Lot;
use App\Models\User;
use App\Support\ResidenceContext;

class LotPolicy
{
    /**
     * Matrice 02 §4 : Lots, tantièmes
     * Admin: R | Gérant: CRUD | Gestionnaire: CRUD | Comptable: R | Conseil: R
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lot $lot): bool
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

    public function update(User $user, Lot $lot): bool
    {
        if ($user->isPlatformAdmin()) {
            return false;
        }

        $residence = ResidenceContext::get() ?? $lot->residence;
        if (! $residence) {
            return false;
        }

        $role = $user->roleInResidence($residence);

        return in_array($role, [
            ResidenceUserRole::Gerant,
            ResidenceUserRole::Gestionnaire,
        ], true);
    }

    public function attachOwner(User $user, Lot $lot): bool
    {
        return $this->update($user, $lot);
    }

    public function mutate(User $user, Lot $lot): bool
    {
        return $this->update($user, $lot);
    }
}

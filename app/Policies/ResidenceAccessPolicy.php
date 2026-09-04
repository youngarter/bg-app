<?php

namespace App\Policies;

use App\Models\ResidenceAccess;
use App\Models\User;

class ResidenceAccessPolicy
{
    /**
     * Matrice 02 §4: Accréditation syndic
     * Admin: CRUD | Gérant: — | Gestionnaire: — | Comptable: — | Conseil: —
     */
    public function view(User $user, ResidenceAccess $access): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, ResidenceAccess $access): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, ResidenceAccess $access): bool
    {
        return $user->isPlatformAdmin();
    }

    public function grant(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function revoke(User $user, ResidenceAccess $access): bool
    {
        return $user->isPlatformAdmin();
    }
}

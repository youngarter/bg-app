<?php

namespace App\Actions\ResidenceAccess;

use App\Enums\LicenseStatus;
use App\Enums\ResidenceAccessStatus;
use App\Exceptions\InvalidLicenseStateException;
use App\Models\AuditLog;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\SyndicCompany;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class GrantResidenceAccessAction
{
    /**
     * Accredit a syndic company on a residence.
     */
    public function handle(Residence $residence, SyndicCompany $syndicCompany, User $admin): ResidenceAccess
    {
        if (! $admin->isPlatformAdmin()) {
            throw new AuthorizationException('Seul un administrateur plateforme peut accorder un accès syndic.');
        }

        $license = $residence->license()->withoutGlobalScopes()->first();
        if ($license && $license->status === LicenseStatus::Suspended) {
            throw new InvalidLicenseStateException('Impossible d\'accréditer un syndic sur une résidence dont la licence est suspendue.');
        }

        return DB::transaction(function () use ($residence, $syndicCompany, $admin) {
            $access = ResidenceAccess::create([
                'residence_id' => $residence->id,
                'syndic_company_id' => $syndicCompany->id,
                'status' => ResidenceAccessStatus::Active,
                'granted_at' => now(),
                'granted_by_admin_id' => $admin->id,
            ]);

            AuditLog::create([
                'residence_id' => $residence->id,
                'actor_user_id' => $admin->id,
                'action' => 'access.granted',
                'auditable_type' => ResidenceAccess::class,
                'auditable_id' => $access->id,
                'motif' => 'Accréditation de la société de syndic '.$syndicCompany->nom,
            ]);

            return $access;
        });
    }
}

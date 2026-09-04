<?php

namespace App\Actions\License;

use App\Enums\LicenseEventType;
use App\Enums\LicensePayer;
use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Residence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CreateLicenseAction
{
    /**
     * @param  array{
     *     plan?: string,
     *     starts_on: CarbonInterface|string,
     *     ends_on: CarbonInterface|string,
     *     grace_days?: int,
     *     payer?: LicensePayer|string,
     * }  $attributes
     */
    public function handle(Residence $residence, array $attributes, ?User $actor = null): License
    {
        return DB::transaction(function () use ($residence, $attributes, $actor) {
            $license = License::create([
                'residence_id' => $residence->id,
                'plan' => $attributes['plan'] ?? 'standard',
                'starts_on' => $attributes['starts_on'],
                'ends_on' => $attributes['ends_on'],
                'grace_days' => $attributes['grace_days'] ?? 30,
                'status' => LicenseStatus::Active,
                'payer' => $attributes['payer'] ?? LicensePayer::Copropriete,
            ]);

            LicenseEvent::create([
                'license_id' => $license->id,
                'type' => LicenseEventType::Created,
                'effective_at' => now(),
                'actor_user_id' => $actor?->id,
                'note' => 'Création initiale de la licence',
            ]);

            AuditLog::create([
                'residence_id' => $residence->id,
                'actor_user_id' => $actor?->id,
                'action' => 'license.created',
                'auditable_type' => License::class,
                'auditable_id' => $license->id,
                'motif' => 'Attribution initiale de licence pour la résidence',
            ]);

            return $license;
        });
    }
}

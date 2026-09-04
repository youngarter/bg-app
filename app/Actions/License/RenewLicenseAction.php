<?php

namespace App\Actions\License;

use App\Enums\LicenseEventType;
use App\Enums\LicenseStatus;
use App\Exceptions\InvalidLicenseStateException;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RenewLicenseAction
{
    /**
     * Renew a license for a new expiration date.
     */
    public function handle(License $license, CarbonInterface|string $newEndsOn, User $actor, ?string $note = null): License
    {
        if (! $actor->isPlatformAdmin()) {
            throw new AuthorizationException('Seul un administrateur plateforme peut renouveler une licence.');
        }

        if ($license->status === LicenseStatus::Suspended) {
            throw new InvalidLicenseStateException('Une licence suspendue ne peut pas être renouvelée directement : réactivez-la d\'abord.');
        }

        return DB::transaction(function () use ($license, $newEndsOn, $actor, $note) {
            $license->ends_on = $newEndsOn;
            $license->status = LicenseStatus::Active;
            $license->save();

            LicenseEvent::create([
                'license_id' => $license->id,
                'type' => LicenseEventType::Renewed,
                'effective_at' => now(),
                'actor_user_id' => $actor->id,
                'note' => $note ?? 'Renouvellement de la licence',
            ]);

            AuditLog::create([
                'residence_id' => $license->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'license.renewed',
                'auditable_type' => License::class,
                'auditable_id' => $license->id,
                'motif' => $note ?? 'Renouvellement administratif de la licence',
            ]);

            return $license;
        });
    }
}

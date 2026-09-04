<?php

namespace App\Actions\License;

use App\Enums\LicenseEventType;
use App\Enums\LicenseStatus;
use App\Exceptions\InvalidLicenseStateException;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ReactivateLicenseAction
{
    /**
     * Reactivate a suspended license with a mandatory motif (>= 10 characters).
     */
    public function handle(License $license, User $actor, string $motif): License
    {
        if (! $actor->isPlatformAdmin()) {
            throw new AuthorizationException('Seul un administrateur plateforme peut réactiver une licence.');
        }

        if ($license->status !== LicenseStatus::Suspended) {
            throw new InvalidLicenseStateException('Seule une licence suspendue peut être réactivée.');
        }

        $trimmedMotif = trim($motif);
        if (mb_strlen($trimmedMotif) < 10) {
            throw new InvalidTransitionMotifException('Le motif de réactivation est obligatoire et doit contenir au moins 10 caractères.');
        }

        return DB::transaction(function () use ($license, $actor, $trimmedMotif) {
            $today = now()->startOfDay();
            $endsOn = $license->ends_on->copy()->startOfDay();
            $graceEnd = $endsOn->copy()->addDays($license->grace_days);

            if ($today->lt($endsOn)) {
                $targetStatus = LicenseStatus::Active;
            } elseif ($today->lte($graceEnd)) {
                $targetStatus = LicenseStatus::Grace;
            } else {
                $targetStatus = LicenseStatus::ReadOnly;
            }

            $license->status = $targetStatus;
            $license->save();

            LicenseEvent::create([
                'license_id' => $license->id,
                'type' => LicenseEventType::Reactivated,
                'effective_at' => now(),
                'actor_user_id' => $actor->id,
                'note' => $trimmedMotif,
            ]);

            AuditLog::create([
                'residence_id' => $license->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'license.reactivated',
                'auditable_type' => License::class,
                'auditable_id' => $license->id,
                'motif' => $trimmedMotif,
            ]);

            return $license;
        });
    }
}

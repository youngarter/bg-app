<?php

namespace App\Actions\License;

use App\Enums\LicenseEventType;
use App\Enums\LicenseStatus;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SuspendLicenseAction
{
    /**
     * Suspend a license with a mandatory motif (>= 10 characters).
     */
    public function handle(License $license, User $actor, string $motif, ?string $documentPath = null): License
    {
        if (! $actor->isPlatformAdmin()) {
            throw new AuthorizationException('Seul un administrateur plateforme peut suspendre une licence.');
        }

        $trimmedMotif = trim($motif);
        if (mb_strlen($trimmedMotif) < 10) {
            throw new InvalidTransitionMotifException('Le motif de suspension est obligatoire et doit contenir au moins 10 caractères.');
        }

        return DB::transaction(function () use ($license, $actor, $trimmedMotif, $documentPath) {
            $license->status = LicenseStatus::Suspended;
            $license->save();

            LicenseEvent::create([
                'license_id' => $license->id,
                'type' => LicenseEventType::Suspended,
                'effective_at' => now(),
                'actor_user_id' => $actor->id,
                'note' => $trimmedMotif,
            ]);

            AuditLog::create([
                'residence_id' => $license->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'license.suspended',
                'auditable_type' => License::class,
                'auditable_id' => $license->id,
                'motif' => $trimmedMotif,
                'document_path' => $documentPath,
            ]);

            return $license;
        });
    }
}

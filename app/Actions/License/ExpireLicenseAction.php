<?php

namespace App\Actions\License;

use App\Enums\LicenseEventType;
use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpireLicenseAction
{
    /**
     * Transition a license automatically to grace or read_only upon expiration.
     */
    public function handle(License $license, LicenseStatus $toStatus): License
    {
        if (! in_array($toStatus, [LicenseStatus::Grace, LicenseStatus::ReadOnly], true)) {
            throw new InvalidArgumentException('Seules les transitions vers grace ou read_only sont supportées par cette action.');
        }

        return DB::transaction(function () use ($license, $toStatus) {
            $license->status = $toStatus;
            $license->save();

            $isGrace = $toStatus === LicenseStatus::Grace;
            $note = $isGrace
                ? 'Passage en période de grâce : date de fin dépassée'
                : 'Passage en lecture seule : période de grâce épuisée';
            $action = $isGrace
                ? 'license.grace_entered'
                : 'license.read_only_entered';

            LicenseEvent::create([
                'license_id' => $license->id,
                'type' => LicenseEventType::Expired,
                'effective_at' => now(),
                'actor_user_id' => null,
                'note' => $note,
            ]);

            AuditLog::create([
                'residence_id' => $license->residence_id,
                'actor_user_id' => null,
                'action' => $action,
                'auditable_type' => License::class,
                'auditable_id' => $license->id,
                'motif' => 'Transition système : '.$note,
            ]);

            return $license;
        });
    }
}

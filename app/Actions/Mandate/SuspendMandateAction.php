<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Exceptions\InvalidStateException;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\Mandate;
use App\Models\User;

class SuspendMandateAction
{
    /**
     * Transition active -> suspended.
     */
    public function handle(Mandate $mandate, string $motif, ?User $actor = null): Mandate
    {
        if ($mandate->state !== MandateState::Active) {
            throw new InvalidStateException('Seul un mandat actif peut être suspendu.');
        }

        if (mb_strlen(trim($motif)) < 10) {
            throw new InvalidTransitionMotifException('Le motif de suspension doit contenir au moins 10 caractères.');
        }

        $mandate->state = MandateState::Suspended;
        $mandate->save();

        if ($actor) {
            AuditLog::create([
                'residence_id' => $mandate->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'mandate.suspended',
                'auditable_type' => Mandate::class,
                'auditable_id' => $mandate->id,
                'motif' => $motif,
            ]);
        }

        return $mandate;
    }
}

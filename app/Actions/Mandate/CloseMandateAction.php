<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Mandate;
use App\Models\User;

class CloseMandateAction
{
    /**
     * Transition expired/terminated -> closed (Irreversible).
     */
    public function handle(Mandate $mandate, ?User $actor = null): Mandate
    {
        if (! in_array($mandate->state, [MandateState::Expired, MandateState::Terminated], true)) {
            throw new InvalidStateException('Seul un mandat expiré ou résilié peut être clôturé.');
        }

        if (! $mandate->allExercisesClosed()) {
            throw new InvalidStateException('Tous les exercices du mandat doivent être clôturés avant de pouvoir clôturer le mandat.');
        }

        $mandate->state = MandateState::Closed;
        $mandate->save();

        // 11 §12.1 : Toute transition irréversible écrit un audit_logs
        AuditLog::create([
            'residence_id' => $mandate->residence_id,
            'actor_user_id' => $actor?->id,
            'action' => 'mandate.closed',
            'auditable_type' => Mandate::class,
            'auditable_id' => $mandate->id,
            'motif' => 'Clôture définitive du mandat après passation et clôture de tous ses exercices',
        ]);

        return $mandate;
    }
}

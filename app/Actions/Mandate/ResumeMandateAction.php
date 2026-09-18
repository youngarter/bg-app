<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Mandate;
use App\Models\User;
use InvalidArgumentException;

class ResumeMandateAction
{
    /**
     * Transition suspended -> active.
     */
    public function handle(Mandate $mandate, ?User $actor = null): Mandate
    {
        if ($mandate->state !== MandateState::Suspended) {
            throw new InvalidStateException('Seul un mandat suspendu peut être réactivé.');
        }

        $overlappingActive = Mandate::query()
            ->where('residence_id', $mandate->residence_id)
            ->where('id', '!=', $mandate->id)
            ->whereIn('state', [MandateState::Active->value, MandateState::Suspended->value])
            ->where(function ($query) use ($mandate) {
                $query->where('started_on', '<=', $mandate->ended_on->toDateString())
                    ->where('ended_on', '>=', $mandate->started_on->toDateString());
            })
            ->exists();

        if ($overlappingActive) {
            throw new InvalidArgumentException('Un autre mandat actif ou suspendu chevauchant cette période existe déjà pour cette résidence.');
        }

        $mandate->state = MandateState::Active;
        $mandate->save();

        if ($actor) {
            AuditLog::create([
                'residence_id' => $mandate->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'mandate.resumed',
                'auditable_type' => Mandate::class,
                'auditable_id' => $mandate->id,
            ]);
        }

        return $mandate;
    }
}

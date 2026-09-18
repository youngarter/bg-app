<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Mandate;
use App\Models\User;
use InvalidArgumentException;

class ActivateMandateAction
{
    /**
     * Transition draft -> active.
     */
    public function handle(Mandate $mandate, ?User $actor = null): Mandate
    {
        if ($mandate->state !== MandateState::Draft) {
            throw new InvalidStateException('Seul un mandat en état brouillon peut être activé.');
        }

        $started = $mandate->started_on;
        $ended = $mandate->ended_on;

        if ($started->gt($ended)) {
            throw new InvalidArgumentException('Les dates du mandat sont incohérentes.');
        }

        $overlappingActive = Mandate::query()
            ->where('residence_id', $mandate->residence_id)
            ->where('id', '!=', $mandate->id)
            ->whereIn('state', [MandateState::Active->value, MandateState::Suspended->value])
            ->where(function ($query) use ($started, $ended) {
                $query->where('started_on', '<=', $ended->toDateString())
                    ->where('ended_on', '>=', $started->toDateString());
            })
            ->exists();

        if ($overlappingActive) {
            throw new InvalidArgumentException('Un mandat actif ou suspendu chevauchant cette période existe déjà pour cette résidence.');
        }

        $mandate->state = MandateState::Active;
        $mandate->save();

        if ($actor) {
            AuditLog::create([
                'residence_id' => $mandate->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'mandate.activated',
                'auditable_type' => Mandate::class,
                'auditable_id' => $mandate->id,
            ]);
        }

        return $mandate;
    }
}

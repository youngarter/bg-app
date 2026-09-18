<?php

namespace App\Actions\Exercise;

use App\Enums\ExerciseState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Exercise;
use App\Models\Mandate;
use App\Models\Residence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CreateExerciseAction
{
    /**
     * Create an accounting exercise.
     * Enforces invariants T1, T2, T3 and T4.
     */
    public function handle(
        Residence $residence,
        Mandate $mandate,
        string $label,
        CarbonInterface|string $startedOn,
        CarbonInterface|string $endedOn,
        bool $open = true,
        ?User $actor = null
    ): Exercise {
        $trimmedLabel = trim($label);
        if ($trimmedLabel === '') {
            throw new InvalidArgumentException('Le libellé de l\'exercice est obligatoire.');
        }

        // Invariant T4 : Chaque exercice appartient à exactement un mandat de cette résidence
        if ($mandate->residence_id !== $residence->id) {
            throw new InvalidArgumentException('Le mandat spécifié n\'appartient pas à cette résidence.');
        }

        $started = $startedOn instanceof CarbonInterface ? $startedOn : Carbon::parse($startedOn);
        $ended = $endedOn instanceof CarbonInterface ? $endedOn : Carbon::parse($endedOn);

        if ($started->gt($ended)) {
            throw new InvalidArgumentException('La date de début doit être antérieure ou égale à la date de fin.');
        }

        // Invariant T2 : [exercise.started_on, ended_on] ⊆ [mandate.started_on, ended_on]
        if ($started->lt($mandate->started_on) || $ended->gt($mandate->ended_on)) {
            throw new InvalidArgumentException('La période de l\'exercice doit être entièrement comprise dans celle de son mandat.');
        }

        // Invariant T3 : Un seul exercice vivant (open ou closing) par résidence à la fois
        if ($open) {
            $hasActiveExercise = Exercise::query()
                ->where('residence_id', $residence->id)
                ->whereIn('state', [ExerciseState::Open->value, ExerciseState::Closing->value])
                ->exists();

            if ($hasActiveExercise) {
                throw new InvalidStateException('Un exercice est déjà en cours (ouvert ou en cours de clôture) pour cette résidence.');
            }
        }

        // Invariant T1 : Les exercices d'une résidence ne se chevauchent jamais
        $overlappingExercise = Exercise::query()
            ->where('residence_id', $residence->id)
            ->where(function ($query) use ($started, $ended) {
                $query->where('started_on', '<=', $ended->toDateString())
                    ->where('ended_on', '>=', $started->toDateString());
            })
            ->exists();

        if ($overlappingExercise) {
            throw new InvalidArgumentException('Les dates de l\'exercice chevauchent un exercice existant de cette résidence.');
        }

        $exercise = Exercise::create([
            'residence_id' => $residence->id,
            'mandate_id' => $mandate->id,
            'label' => $trimmedLabel,
            'started_on' => $started->toDateString(),
            'ended_on' => $ended->toDateString(),
            'state' => $open ? ExerciseState::Open : ExerciseState::Closed,
        ]);

        if ($actor) {
            AuditLog::create([
                'residence_id' => $residence->id,
                'actor_user_id' => $actor->id,
                'action' => 'exercise.created',
                'auditable_type' => Exercise::class,
                'auditable_id' => $exercise->id,
            ]);
        }

        return $exercise;
    }
}

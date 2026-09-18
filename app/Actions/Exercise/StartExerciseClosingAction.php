<?php

namespace App\Actions\Exercise;

use App\Enums\ExerciseState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Exercise;
use App\Models\User;

class StartExerciseClosingAction
{
    /**
     * Transition open -> closing.
     * Saisie gelée pour contrôles préalables de clôture.
     */
    public function handle(Exercise $exercise, ?User $actor = null): Exercise
    {
        if ($exercise->state !== ExerciseState::Open) {
            throw new InvalidStateException('Seul un exercice ouvert peut être mis en cours de clôture.');
        }

        $exercise->state = ExerciseState::Closing;
        $exercise->save();

        if ($actor) {
            AuditLog::create([
                'residence_id' => $exercise->residence_id,
                'actor_user_id' => $actor->id,
                'action' => 'exercise.closing_started',
                'auditable_type' => Exercise::class,
                'auditable_id' => $exercise->id,
            ]);
        }

        return $exercise;
    }
}

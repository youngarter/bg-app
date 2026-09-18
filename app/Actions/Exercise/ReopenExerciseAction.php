<?php

namespace App\Actions\Exercise;

use App\Enums\ExerciseState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Exercise;
use App\Models\User;

class ReopenExerciseAction
{
    /**
     * Transition closing -> open (e.g. controls failed or cancellation).
     */
    public function handle(Exercise $exercise, ?string $motif = null, ?User $actor = null): Exercise
    {
        if ($exercise->state !== ExerciseState::Closing) {
            throw new InvalidStateException('Seul un exercice en cours de clôture peut être rouvert.');
        }

        $exercise->state = ExerciseState::Open;
        $exercise->save();

        if ($actor || $motif) {
            AuditLog::create([
                'residence_id' => $exercise->residence_id,
                'actor_user_id' => $actor?->id,
                'action' => 'exercise.reopened',
                'auditable_type' => Exercise::class,
                'auditable_id' => $exercise->id,
                'motif' => $motif ?: 'Réouverture de l\'exercice suite à anomalie ou annulation de la clôture',
            ]);
        }

        return $exercise;
    }
}

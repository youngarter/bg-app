<?php

namespace App\Services;

use App\Enums\ExerciseState;
use App\Enums\MandateState;
use App\Models\Residence;

class ResidenceOnboardingService
{
    /**
     * Compute the onboarding progression in real time from database state.
     * No state flag is ever persisted.
     *
     * @return array{is_complete: bool, steps: array<int, array<string, mixed>>}
     */
    public static function compute(Residence $residence): array
    {
        $now = now()->toDateString();

        // Étape 1 : Informations de la résidence
        $infoComplete = (bool) (
            $residence->nom
            && $residence->adresse
            && $residence->ville
            && $residence->total_tantiemes > 0
        );

        // Étape 2 : Mandat de syndic actif à ce jour
        $hasActiveMandate = $residence->mandates()
            ->where('state', MandateState::Active->value)
            ->where('started_on', '<=', $now)
            ->where('ended_on', '>=', $now)
            ->exists();

        // Étape 3 : Exercice comptable ouvert
        $hasOpenExercise = $residence->exercises()
            ->where('state', ExerciseState::Open->value)
            ->exists();

        // Étape 4 : Lots saisis
        $lotsCount = $residence->lots()->count();
        $hasLots = $lotsCount > 0;

        // Étape 5 : Copropriétaires et cohérence des tantièmes
        $hasOwnersWithOwnership = $residence->owners()->whereHas('ownerships')->exists();
        $sumTantiemes = $residence->sumTantiemes();
        $totalTantiemes = (int) $residence->total_tantiemes;
        $tantiemesConsistent = $totalTantiemes > 0 && $sumTantiemes === $totalTantiemes;

        $tantiemesDiscrepancy = $totalTantiemes - $sumTantiemes;
        $discrepancyPercentage = $totalTantiemes > 0
            ? round(($tantiemesDiscrepancy / $totalTantiemes) * 100, 2)
            : 0;

        $ownershipsComplete = $hasOwnersWithOwnership && $tantiemesConsistent;

        $steps = [
            [
                'index' => 1,
                'key' => 'residence_info',
                'title' => 'Informations de la résidence',
                'description' => 'Nom, adresse complète et total des tantièmes de référence.',
                'completed' => $infoComplete,
                'href' => route('dashboard'),
            ],
            [
                'index' => 2,
                'key' => 'mandate',
                'title' => 'Mandat de syndic actif',
                'description' => 'Désignation de la société de syndic et période de mandat en cours.',
                'completed' => $hasActiveMandate,
                'href' => route('dashboard'),
            ],
            [
                'index' => 3,
                'key' => 'exercise',
                'title' => 'Exercice comptable ouvert',
                'description' => 'Exercice en cours pour enregistrer les futures opérations comptables.',
                'completed' => $hasOpenExercise,
                'href' => route('dashboard'),
            ],
            [
                'index' => 4,
                'key' => 'lots',
                'title' => 'Saisie des lots',
                'description' => 'Définition des appartements, parkings, caves avec leurs tantièmes respectifs.',
                'completed' => $hasLots,
                'lots_count' => $lotsCount,
                'href' => route('lots.index'),
            ],
            [
                'index' => 5,
                'key' => 'ownerships',
                'title' => 'Copropriétaires et tantièmes',
                'description' => 'Affectation des détenteurs et équilibre total des tantièmes.',
                'completed' => $ownershipsComplete,
                'has_owners' => $hasOwnersWithOwnership,
                'sum_tantiemes' => $sumTantiemes,
                'total_tantiemes' => $totalTantiemes,
                'tantiemes_consistent' => $tantiemesConsistent,
                'discrepancy' => $tantiemesDiscrepancy,
                'discrepancy_percentage' => $discrepancyPercentage,
                'href' => route('owners.index'),
            ],
        ];

        $isComplete = $infoComplete && $hasActiveMandate && $hasOpenExercise && $hasLots && $ownershipsComplete;

        return [
            'is_complete' => $isComplete,
            'steps' => $steps,
        ];
    }
}

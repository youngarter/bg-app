<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseState;
use App\Enums\MandateState;
use App\Services\ResidenceOnboardingService;
use App\Support\ResidenceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the structural dashboard (zero financial figures).
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $residence = ResidenceContext::get();

        if (! $residence) {
            $accessible = $user ? $user->accessibleResidences() : collect();

            return Inertia::render('dashboard', [
                'hasNoResidences' => $accessible->isEmpty(),
                'residence' => null,
                'structure' => null,
                'onboarding' => null,
                'isStructureEmpty' => true,
            ]);
        }

        $now = now()->toDateString();

        $activeMandate = $residence->mandates()
            ->where('state', MandateState::Active->value)
            ->where('started_on', '<=', $now)
            ->where('ended_on', '>=', $now)
            ->with('syndicCompany')
            ->first();

        $openExercise = $residence->exercises()
            ->where('state', ExerciseState::Open->value)
            ->first();

        $lotsCount = $residence->lots()->count();
        $ownersCount = $residence->owners()->count();
        $sumTantiemes = $residence->sumTantiemes();
        $totalTantiemes = (int) $residence->total_tantiemes;

        $onboarding = ResidenceOnboardingService::compute($residence);

        $isStructureEmpty = ($lotsCount === 0 && ! $activeMandate && ! $openExercise);

        return Inertia::render('dashboard', [
            'hasNoResidences' => false,
            'residence' => [
                'id' => $residence->id,
                'nom' => $residence->nom,
                'adresse' => $residence->adresse,
                'ville' => $residence->ville,
                'code_postal' => $residence->code_postal,
                'total_tantiemes' => $totalTantiemes,
            ],
            'structure' => [
                'active_mandate' => $activeMandate ? [
                    'id' => $activeMandate->id,
                    'syndic_nom' => $activeMandate->syndicCompany->nom,
                    'started_on' => $activeMandate->started_on->toDateString(),
                    'ended_on' => $activeMandate->ended_on->toDateString(),
                    'pv_ag_reference' => $activeMandate->pv_ag_reference,
                ] : null,
                'open_exercise' => $openExercise ? [
                    'id' => $openExercise->id,
                    'label' => $openExercise->label,
                    'started_on' => $openExercise->started_on->toDateString(),
                    'ended_on' => $openExercise->ended_on->toDateString(),
                ] : null,
                'lots_count' => $lotsCount,
                'owners_count' => $ownersCount,
                'sum_tantiemes' => $sumTantiemes,
                'total_tantiemes' => $totalTantiemes,
                'tantiemes_consistent' => ($totalTantiemes > 0 && $sumTantiemes === $totalTantiemes),
            ],
            'onboarding' => $onboarding,
            'isStructureEmpty' => $isStructureEmpty,
        ]);
    }
}

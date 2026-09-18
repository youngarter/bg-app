<?php

use App\Enums\ExerciseState;
use App\Enums\MandateState;
use App\Enums\OwnershipNature;
use App\Models\Exercise;
use App\Models\Lot;
use App\Models\LotOwnership;
use App\Models\Mandate;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\SyndicCompany;
use App\Services\ResidenceOnboardingService;

beforeEach(function () {
    $this->artisan('migrate');
});

test('tantiemes percentage calculation on a residence with total_tantiemes != 10000 (Point 1 defect test)', function () {
    // Residence with total_tantiemes = 8 000 (not 10 000)
    $residence = Residence::factory()->create([
        'total_tantiemes' => 8000,
    ]);

    // Lot with 150 tantièmes
    $lot = Lot::factory()->create([
        'residence_id' => $residence->id,
        'tantiemes' => 150,
    ]);

    // Formula 1: lot tantiemes relative to residence: (150 / 8000) * 100 = 1.875% (NOT 1.50%)
    $relativePercentage = ($lot->tantiemes / $residence->total_tantiemes) * 100;
    expect($relativePercentage)->toBe(1.875);

    // Formula 2: lot ownership quote_part in basis points (10 000 = 100% of this lot)
    $quotePartBp = 10000;
    $ownershipPercentage = $quotePartBp / 100;
    expect($ownershipPercentage)->toBe(100);

    // Discrepancy on onboarding stepper
    $onboarding = ResidenceOnboardingService::compute($residence);
    $step5 = $onboarding['steps'][4]; // ownerships step

    expect($step5['total_tantiemes'])->toBe(8000);
    expect($step5['sum_tantiemes'])->toBe(150);
    expect($step5['discrepancy'])->toBe(7850); // 8000 - 150
    // (7850 / 8000) * 100 = 98.125 -> 98.13%
    expect($step5['discrepancy_percentage'])->toBe(98.13);
    expect($step5['tantiemes_consistent'])->toBeFalse();
    expect($onboarding['is_complete'])->toBeFalse();
});

test('onboarding stepper marks steps completed dynamically without persisted flag', function () {
    $residence = Residence::factory()->create([
        'nom' => 'Résidence Oasis',
        'adresse' => '10 Boulevard Zerktouni',
        'ville' => 'Casablanca',
        'total_tantiemes' => 5000,
    ]);

    $syndic = SyndicCompany::factory()->create();

    // Initially: step 1 is complete (residence info), rest are incomplete
    $initial = ResidenceOnboardingService::compute($residence);
    expect($initial['steps'][0]['completed'])->toBeTrue();
    expect($initial['steps'][1]['completed'])->toBeFalse(); // mandate
    expect($initial['steps'][2]['completed'])->toBeFalse(); // exercise
    expect($initial['steps'][3]['completed'])->toBeFalse(); // lots
    expect($initial['steps'][4]['completed'])->toBeFalse(); // ownerships
    expect($initial['is_complete'])->toBeFalse();

    // 1. Add active mandate
    Mandate::factory()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
        'state' => MandateState::Active,
        'started_on' => now()->subMonth(),
        'ended_on' => now()->addYear(),
    ]);

    $afterMandate = ResidenceOnboardingService::compute($residence);
    expect($afterMandate['steps'][1]['completed'])->toBeTrue();

    // 2. Add open exercise
    Exercise::factory()->create([
        'residence_id' => $residence->id,
        'state' => ExerciseState::Open,
        'started_on' => now()->startOfYear(),
        'ended_on' => now()->endOfYear(),
    ]);

    $afterExercise = ResidenceOnboardingService::compute($residence);
    expect($afterExercise['steps'][2]['completed'])->toBeTrue();

    // 3. Add lots totaling 5 000 tantièmes
    $lot1 = Lot::factory()->create([
        'residence_id' => $residence->id,
        'tantiemes' => 2500,
    ]);
    $lot2 = Lot::factory()->create([
        'residence_id' => $residence->id,
        'tantiemes' => 2500,
    ]);

    $afterLots = ResidenceOnboardingService::compute($residence);
    expect($afterLots['steps'][3]['completed'])->toBeTrue();
    expect($afterLots['steps'][4]['completed'])->toBeFalse(); // no owners attached yet

    // 4. Add owner and attach to lot
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);
    LotOwnership::create([
        'residence_id' => $residence->id,
        'lot_id' => $lot1->id,
        'owner_id' => $owner->id,
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete->value,
        'started_on' => now()->toDateString(),
    ]);

    $complete = ResidenceOnboardingService::compute($residence);
    expect($complete['steps'][4]['completed'])->toBeTrue();
    expect($complete['steps'][4]['tantiemes_consistent'])->toBeTrue();
    expect($complete['steps'][4]['discrepancy'])->toBe(0);
    expect($complete['is_complete'])->toBeTrue();
});

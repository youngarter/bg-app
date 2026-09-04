<?php

use App\Actions\Delegation\CreateDelegationAction;
use App\Enums\DelegationState;
use App\Enums\DelegationTitle;
use App\Models\Delegation;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\User;
use App\Support\ResidenceContext;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    ResidenceContext::forget();

    Route::middleware(['web', 'residence.attachment'])->group(function () {
        Route::get('/test-delegation/{residence}/action', fn () => response()->json(['ok' => true]));
    });
});

test('02 §2.5 rule 1: strict eligibility - only an owner of the residence can be delegated', function () {
    $residenceA = Residence::factory()->create();
    $residenceB = Residence::factory()->create();

    $user = User::factory()->create();
    $ownerOfResidenceB = Owner::factory()->create([
        'residence_id' => $residenceB->id,
        'user_id' => $user->id,
    ]);

    $action = app(CreateDelegationAction::class);

    // Tentative de déléguer un owner de la résidence B sur la résidence A
    expect(fn () => $action->handle($residenceA, $ownerOfResidenceB, $user, [
        'titre' => DelegationTitle::ViceSyndic,
        'modules' => ['appels', 'paiements'],
        'started_on' => now(),
        'ended_on' => now()->addYear(),
    ]))->toThrow(InvalidArgumentException::class, 'Éligibilité stricte violée');
});

test('02 §2.5 rule 6: whitelist tested - unknown or empty modules are rejected', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();
    $owner = Owner::factory()->create([
        'residence_id' => $residence->id,
        'user_id' => $user->id,
    ]);

    $action = app(CreateDelegationAction::class);

    // Liste vide
    expect(fn () => $action->handle($residence, $owner, $user, [
        'titre' => DelegationTitle::Delegue,
        'modules' => [],
        'started_on' => now(),
        'ended_on' => now()->addYear(),
    ]))->toThrow(InvalidArgumentException::class);

    // Module non autorisé / inconnu
    expect(fn () => $action->handle($residence, $owner, $user, [
        'titre' => DelegationTitle::Delegue,
        'modules' => ['module_inconnu_pirate'],
        'started_on' => now(),
        'ended_on' => now()->addYear(),
    ]))->toThrow(InvalidArgumentException::class);
});

test('02 §2.5 rule 6: delegation model allowsModule only allows whitelisted modules and never interprets unknown as allow all', function () {
    $delegation = Delegation::factory()->create([
        'modules' => ['appels', 'paiements'],
    ]);

    expect($delegation->allowsModule('appels'))->toBeTrue();
    expect($delegation->allowsModule('paiements'))->toBeTrue();
    expect($delegation->allowsModule('tresorerie'))->toBeFalse();
    expect($delegation->allowsModule('unknown_module'))->toBeFalse();
});

test('02 §2.5 rule 5: a delegation never grants approval rights', function () {
    $delegation = Delegation::factory()->create([
        'titre' => DelegationTitle::ViceSyndic,
        'modules' => Delegation::VALID_MODULES,
    ]);

    expect($delegation->canApprove())->toBeFalse();
});

test('verrou 2: active delegation grants access to residence', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();
    $owner = Owner::factory()->create([
        'residence_id' => $residence->id,
        'user_id' => $user->id,
    ]);

    Delegation::factory()->create([
        'residence_id' => $residence->id,
        'owner_id' => $owner->id,
        'user_id' => $user->id,
        'state' => DelegationState::Active,
        'started_on' => now()->subMonth(),
        'ended_on' => now()->addMonths(6),
    ]);

    $this->actingAs($user)
        ->getJson("/test-delegation/{$residence->id}/action")
        ->assertSuccessful();
});

test('verrou 2: expired delegation is denied with 403 without any cron job', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();
    $owner = Owner::factory()->create([
        'residence_id' => $residence->id,
        'user_id' => $user->id,
    ]);

    Delegation::factory()->create([
        'residence_id' => $residence->id,
        'owner_id' => $owner->id,
        'user_id' => $user->id,
        'state' => DelegationState::Active,
        'started_on' => now()->subYear(),
        'ended_on' => now()->subDay(), // Expiré hier
    ]);

    $this->actingAs($user)
        ->getJson("/test-delegation/{$residence->id}/action")
        ->assertForbidden();
});

test('verrou 2: revoked delegation is denied with 403', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();
    $owner = Owner::factory()->create([
        'residence_id' => $residence->id,
        'user_id' => $user->id,
    ]);

    Delegation::factory()->create([
        'residence_id' => $residence->id,
        'owner_id' => $owner->id,
        'user_id' => $user->id,
        'state' => DelegationState::Revoquee,
        'started_on' => now()->subMonth(),
        'ended_on' => now()->addMonths(6),
    ]);

    $this->actingAs($user)
        ->getJson("/test-delegation/{$residence->id}/action")
        ->assertForbidden();
});

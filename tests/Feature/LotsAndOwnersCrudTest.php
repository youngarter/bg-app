<?php

use App\Enums\LicenseStatus;
use App\Enums\LotType;
use App\Enums\OwnershipNature;
use App\Enums\OwnerType;
use App\Models\License;
use App\Models\Lot;
use App\Models\LotOwnership;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\SyndicCompany;
use App\Models\User;
use App\Support\ResidenceContext;

beforeEach(function () {
    $this->artisan('migrate');

    $this->user = User::factory()->create();
    $this->syndic = SyndicCompany::factory()->create();
    $this->syndic->users()->attach($this->user->id, ['role' => 'gestionnaire']);

    $this->residence = Residence::factory()->create(['total_tantiemes' => 10000]);
    License::factory()->create([
        'residence_id' => $this->residence->id,
        'status' => LicenseStatus::Active,
    ]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $this->residence->id,
        'syndic_company_id' => $this->syndic->id,
    ]);

    $this->actingAs($this->user);
    session(['current_residence_id' => $this->residence->id]);
    ResidenceContext::set($this->residence);
});

test('lot creation automatically provisions auxiliary account and assigns tantiemes', function () {
    $response = $this->post(route('lots.store'), [
        'reference' => 'LOT-A10',
        'type' => LotType::Appartement->value,
        'batiment' => 'Bâtiment 1',
        'etage' => '2ème',
        'superficie' => 85.5,
        'tantiemes' => 150,
    ]);

    $response->assertRedirect();

    $lot = Lot::where('reference', 'LOT-A10')->where('residence_id', $this->residence->id)->first();
    expect($lot)->not->toBeNull();
    expect($lot->tantiemes)->toBe(150);
    expect($lot->account)->not->toBeNull();
    expect($lot->account->code)->not->toBeEmpty();
});

test('lot creation rejects duplicate reference in the same residence', function () {
    Lot::factory()->create([
        'residence_id' => $this->residence->id,
        'reference' => 'LOT-DUP',
    ]);

    $response = $this->post(route('lots.store'), [
        'reference' => 'LOT-DUP',
        'type' => LotType::Appartement->value,
        'tantiemes' => 100,
    ]);

    $response->assertSessionHasErrors(['reference']);
});

test('lot can be updated successfully', function () {
    $lot = Lot::factory()->create([
        'residence_id' => $this->residence->id,
        'reference' => 'LOT-ORIG',
        'tantiemes' => 100,
    ]);

    $response = $this->put(route('lots.update', $lot->id), [
        'reference' => 'LOT-MODIF',
        'type' => LotType::Bureau->value,
        'tantiemes' => 120,
    ]);

    $response->assertRedirect();
    $lot->refresh();
    expect($lot->reference)->toBe('LOT-MODIF');
    expect($lot->tantiemes)->toBe(120);
});

test('owner creation handles personne physique and personne morale', function () {
    // Personne physique
    $responsePhysique = $this->post(route('owners.store'), [
        'type' => OwnerType::PersonnePhysique->value,
        'nom' => 'Benali',
        'prenom' => 'Karim',
        'cin' => 'AB123456',
        'email' => 'karim@example.ma',
    ]);
    $responsePhysique->assertRedirect();

    $physique = Owner::where('cin', 'AB123456')->first();
    expect($physique)->not->toBeNull();
    expect($physique->user_id)->toBeNull(); // user_id is optional

    // Personne morale
    $responseMorale = $this->post(route('owners.store'), [
        'type' => OwnerType::PersonneMorale->value,
        'raison_sociale' => 'Holding Atlas SARL',
        'ice' => '009876543210000',
    ]);
    $responseMorale->assertRedirect();

    $morale = Owner::where('ice', '009876543210000')->first();
    expect($morale)->not->toBeNull();
});

test('attaching owner to lot and mutating ownership', function () {
    $lot = Lot::factory()->create(['residence_id' => $this->residence->id]);
    $ownerA = Owner::factory()->create(['residence_id' => $this->residence->id]);
    $ownerB = Owner::factory()->create(['residence_id' => $this->residence->id]);

    // Initial attach
    $attachResponse = $this->post(route('lots.ownerships.store', $lot->id), [
        'owner_id' => $ownerA->id,
        'nature' => OwnershipNature::PleinePropriete->value,
        'quote_part' => 10000,
        'started_on' => '2025-01-01',
    ]);
    $attachResponse->assertRedirect();

    $ownershipA = LotOwnership::where('lot_id', $lot->id)->where('owner_id', $ownerA->id)->first();
    expect($ownershipA)->not->toBeNull();
    expect($ownershipA->ended_on)->toBeNull();

    // Mutate to owner B
    $mutateResponse = $this->post(route('lots.mutate', $lot->id), [
        'buyer_id' => $ownerB->id,
        'nature' => OwnershipNature::PleinePropriete->value,
        'quote_part' => 10000,
        'effective_date' => '2025-06-01',
    ]);
    $mutateResponse->assertRedirect();

    $ownershipA->refresh();
    expect($ownershipA->ended_on?->toDateString())->toBe('2025-05-31');

    $ownershipB = LotOwnership::where('lot_id', $lot->id)->where('owner_id', $ownerB->id)->first();
    expect($ownershipB)->not->toBeNull();
    expect($ownershipB->started_on->toDateString())->toBe('2025-06-01');
    expect($ownershipB->ended_on)->toBeNull();
});

test('overlapping ownership for same owner on lot catches PostgreSQL EXCLUDE constraint and returns 422 with clean error', function () {
    $lot = Lot::factory()->create(['residence_id' => $this->residence->id]);
    $ownerA = Owner::factory()->create(['residence_id' => $this->residence->id]);

    // Existing ownership from 2025-01-01 (ongoing)
    LotOwnership::create([
        'lot_id' => $lot->id,
        'residence_id' => $this->residence->id,
        'owner_id' => $ownerA->id,
        'nature' => OwnershipNature::PleinePropriete->value,
        'quote_part' => 10000,
        'started_on' => '2025-01-01',
        'ended_on' => null,
    ]);

    // Attempting to attach another active ownership for ownerA on overlapping period directly
    $response = $this->post(route('lots.ownerships.store', $lot->id), [
        'owner_id' => $ownerA->id,
        'nature' => OwnershipNature::PleinePropriete->value,
        'quote_part' => 10000,
        'started_on' => '2025-03-01',
    ]);

    // Must return 422 with clean session error, NOT a 500 QueryException!
    $response->assertSessionHasErrors(['owner_id']);
});

test('destroy routes DO NOT exist for lots or owners (no delete allowed)', function () {
    $lot = Lot::factory()->create(['residence_id' => $this->residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $this->residence->id]);

    // DELETE /lots/{lot}
    $lotDeleteResponse = $this->delete("/lots/{$lot->id}");
    expect(in_array($lotDeleteResponse->status(), [404, 405], true))->toBeTrue();

    // DELETE /owners/{owner}
    $ownerDeleteResponse = $this->delete("/owners/{$owner->id}");
    expect(in_array($ownerDeleteResponse->status(), [404, 405], true))->toBeTrue();
});

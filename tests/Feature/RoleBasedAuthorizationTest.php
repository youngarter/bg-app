<?php

use App\Enums\LicenseStatus;
use App\Enums\LotType;
use App\Enums\OwnerType;
use App\Models\License;
use App\Models\Lot;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\ResidenceRole;
use App\Models\SyndicCompany;
use App\Models\User;
use App\Support\ResidenceContext;

beforeEach(function () {
    $this->artisan('migrate');

    $this->residence = Residence::factory()->create();
    License::factory()->create([
        'residence_id' => $this->residence->id,
        'status' => LicenseStatus::Active,
    ]);
});

test('le président du conseil ne peut pas créer un lot (403 Forbidden)', function () {
    $presidentCS = User::factory()->create();
    ResidenceRole::factory()->president()->create([
        'residence_id' => $this->residence->id,
        'user_id' => $presidentCS->id,
    ]);

    $this->actingAs($presidentCS);
    session(['current_residence_id' => $this->residence->id]);
    ResidenceContext::set($this->residence);

    // Can view lots list
    $this->get(route('lots.index'))->assertOk();

    // CANNOT create a lot
    $response = $this->post(route('lots.store'), [
        'reference' => 'LOT-CS-1',
        'type' => LotType::Appartement->value,
        'tantiemes' => 100,
    ]);

    $response->assertForbidden();
});

test('le membre du conseil ne peut pas créer un copropriétaire (403 Forbidden)', function () {
    $membreCS = User::factory()->create();
    ResidenceRole::factory()->membre()->create([
        'residence_id' => $this->residence->id,
        'user_id' => $membreCS->id,
    ]);

    $this->actingAs($membreCS);
    session(['current_residence_id' => $this->residence->id]);
    ResidenceContext::set($this->residence);

    // Can view owners list
    $this->get(route('owners.index'))->assertOk();

    // CANNOT create an owner
    $response = $this->post(route('owners.store'), [
        'type' => OwnerType::PersonnePhysique->value,
        'nom' => 'Test',
        'prenom' => 'Membre',
    ]);

    $response->assertForbidden();
});

test('le gérant et le gestionnaire du syndic peuvent créer et mettre à jour des lots et copropriétaires', function () {
    $syndic = SyndicCompany::factory()->create();
    $gestionnaire = User::factory()->create();
    $syndic->users()->attach($gestionnaire->id, ['role' => 'gestionnaire']);

    ResidenceAccess::factory()->active()->create([
        'residence_id' => $this->residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($gestionnaire);
    session(['current_residence_id' => $this->residence->id]);
    ResidenceContext::set($this->residence);

    // Can create lot
    $lotResponse = $this->post(route('lots.store'), [
        'reference' => 'LOT-GEST-1',
        'type' => LotType::Appartement->value,
        'tantiemes' => 200,
    ]);
    $lotResponse->assertRedirect();

    $lot = Lot::where('reference', 'LOT-GEST-1')->where('residence_id', $this->residence->id)->first();
    expect($lot)->not->toBeNull();

    // Can update lot
    $updateResponse = $this->put(route('lots.update', $lot->id), [
        'reference' => 'LOT-GEST-1-MOD',
        'type' => LotType::Appartement->value,
        'tantiemes' => 220,
    ]);
    $updateResponse->assertRedirect();

    // Can create owner
    $ownerResponse = $this->post(route('owners.store'), [
        'type' => OwnerType::PersonnePhysique->value,
        'nom' => 'Alami',
        'prenom' => 'Ahmed',
    ]);
    $ownerResponse->assertRedirect();
});

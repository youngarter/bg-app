<?php

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\SyndicCompany;
use App\Models\User;
use App\Support\ResidenceContext;

beforeEach(function () {
    $this->artisan('migrate');
});

test('active license allows both read and write operations', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    $residence = Residence::factory()->create();
    License::factory()->create([
        'residence_id' => $residence->id,
        'status' => LicenseStatus::Active,
    ]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($user);
    session(['current_residence_id' => $residence->id]);
    ResidenceContext::set($residence);

    // Read succeeds
    $this->get(route('lots.index'))->assertOk();

    // Write succeeds
    $response = $this->post(route('lots.store'), [
        'reference' => 'LOT-101',
        'type' => 'appartement',
        'tantiemes' => 100,
    ]);
    $response->assertRedirect();
});

test('grace period license allows both read and write operations', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    $residence = Residence::factory()->create();
    License::factory()->create([
        'residence_id' => $residence->id,
        'status' => LicenseStatus::Grace,
    ]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($user);
    session(['current_residence_id' => $residence->id]);
    ResidenceContext::set($residence);

    // Read succeeds
    $this->get(route('lots.index'))->assertOk();

    // Write succeeds
    $response = $this->post(route('lots.store'), [
        'reference' => 'LOT-102',
        'type' => 'appartement',
        'tantiemes' => 100,
    ]);
    $response->assertRedirect();
});

test('read_only license allows read (GET 200) but blocks write (POST 403)', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    $residence = Residence::factory()->create();
    License::factory()->create([
        'residence_id' => $residence->id,
        'status' => LicenseStatus::ReadOnly,
    ]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($user);
    session(['current_residence_id' => $residence->id]);
    ResidenceContext::set($residence);

    // Read succeeds (GET 200)
    $this->get(route('lots.index'))->assertOk();
    $this->get(route('owners.index'))->assertOk();

    // Write fails with 403
    $response = $this->post(route('lots.store'), [
        'reference' => 'LOT-103',
        'type' => 'appartement',
        'tantiemes' => 100,
    ]);
    $response->assertForbidden();
});

test('suspended license blocks access with 403 but suspended info page is accessible', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    $residence = Residence::factory()->create();
    License::factory()->create([
        'residence_id' => $residence->id,
        'status' => LicenseStatus::Suspended,
    ]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($user);
    session(['current_residence_id' => $residence->id]);
    ResidenceContext::set($residence);

    // Protected route blocked with 403
    $this->get(route('lots.index'))->assertForbidden();

    // Dedicated suspended page is accessible
    $this->get(route('residences.suspended'))->assertOk();
});

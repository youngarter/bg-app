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

test('authenticated user can select an accessible residence', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    $residenceA = Residence::factory()->create(['nom' => 'Résidence Atlas']);
    $residenceB = Residence::factory()->create(['nom' => 'Résidence Majorelle']);

    License::factory()->create(['residence_id' => $residenceA->id, 'status' => LicenseStatus::Active]);
    License::factory()->create(['residence_id' => $residenceB->id, 'status' => LicenseStatus::Active]);

    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residenceA->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('residences.select', $residenceA->id));

    $response->assertRedirect();
    $response->assertSessionHas('current_residence_id', $residenceA->id);
    expect(ResidenceContext::getId())->toBe($residenceA->id);
});

test('user cannot select a residence they are not attached to (returns 403)', function () {
    $user = User::factory()->create();
    $unattachedResidence = Residence::factory()->create(['nom' => 'Résidence Inaccessible']);
    License::factory()->create(['residence_id' => $unattachedResidence->id, 'status' => LicenseStatus::Active]);

    $this->actingAs($user);

    $response = $this->post(route('residences.select', $unattachedResidence->id));

    $response->assertForbidden();
});

test('automatic selection orders residences deterministically by name', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    $residenceZ = Residence::factory()->create(['nom' => 'Résidence Zénith']);
    $residenceA = Residence::factory()->create(['nom' => 'Résidence Al Manar']);

    License::factory()->create(['residence_id' => $residenceZ->id, 'status' => LicenseStatus::Active]);
    License::factory()->create(['residence_id' => $residenceA->id, 'status' => LicenseStatus::Active]);

    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residenceZ->id,
        'syndic_company_id' => $syndic->id,
    ]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residenceA->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $this->actingAs($user);

    // Visiting dashboard without session current_residence_id defaults to Al Manar (first alphabetically)
    $response = $this->get(route('dashboard'));
    $response->assertOk();

    expect(session('current_residence_id'))->toBe($residenceA->id);
    expect(ResidenceContext::getId())->toBe($residenceA->id);
});

test('user with zero accessible residences sees empty state on dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();

    // No residence selected in session or context
    expect(session('current_residence_id'))->toBeNull();
    expect(ResidenceContext::get())->toBeNull();
});

test('explicit {residence} route parameter takes strict priority over session context (anti-tampering test)', function () {
    $user = User::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $syndic->users()->attach($user->id, ['role' => 'gestionnaire']);

    // Residence 1: user is attached
    $authorizedResidence = Residence::factory()->create(['nom' => 'Ma Copropriété']);
    License::factory()->create(['residence_id' => $authorizedResidence->id, 'status' => LicenseStatus::Active]);
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $authorizedResidence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    // Residence 2: user is NOT attached
    $foreignResidence = Residence::factory()->create(['nom' => 'Copropriété d Autrui']);
    License::factory()->create(['residence_id' => $foreignResidence->id, 'status' => LicenseStatus::Active]);

    $this->actingAs($user);

    // Set authorized residence in session
    session(['current_residence_id' => $authorizedResidence->id]);
    ResidenceContext::set($authorizedResidence);

    // User attempts to select or target foreignResidence via route parameter
    $response = $this->post(route('residences.select', $foreignResidence->id));

    // Must be rejected with 403, session context cannot bypass the explicit route param
    $response->assertForbidden();
});

test('platform admin can select and access any residence', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $residence = Residence::factory()->create(['nom' => 'Copropriété Quelconque']);
    License::factory()->create(['residence_id' => $residence->id, 'status' => LicenseStatus::Active]);

    $this->actingAs($admin);

    $response = $this->post(route('residences.select', $residence->id));

    $response->assertRedirect();
    expect(session('current_residence_id'))->toBe($residence->id);
});

<?php

use App\Enums\ResidenceRoleType;
use App\Enums\SyndicRole;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\ResidenceRole;
use App\Models\SyndicCompany;
use App\Models\SyndicCompanyUser;
use App\Models\User;
use App\Support\ResidenceContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    ResidenceContext::forget();

    Route::middleware(['web', 'residence.license'])->group(function () {
        Route::get('/test-locks/{residence}/read', fn () => response()->json(['ok' => true]));
        Route::post('/test-locks/{residence}/write', fn () => response()->json(['ok' => true]));
    });

    Route::middleware(['web', 'residence.attachment'])->group(function () {
        Route::get('/test-locks/{residence}/attachment', fn () => response()->json(['ok' => true]));
    });
});

/*
|--------------------------------------------------------------------------
| Verrou 1: Licence de résidence (CheckResidenceLicense)
|--------------------------------------------------------------------------
*/

test('verrou 1: active license allows both read and write operations', function () {
    $residence = Residence::factory()->create();
    License::factory()->active()->create(['residence_id' => $residence->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/read")
        ->assertSuccessful();

    $this->actingAs($user)
        ->postJson("/test-locks/{$residence->id}/write")
        ->assertSuccessful();
});

test('verrou 1: grace period license allows both read and write operations', function () {
    $residence = Residence::factory()->create();
    License::factory()->grace()->create(['residence_id' => $residence->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/read")
        ->assertSuccessful();

    $this->actingAs($user)
        ->postJson("/test-locks/{$residence->id}/write")
        ->assertSuccessful();
});

test('verrou 1: read_only license allows read but blocks write with 403', function () {
    $residence = Residence::factory()->create();
    License::factory()->readOnly()->create(['residence_id' => $residence->id]);
    $user = User::factory()->create();

    // Read autorisé
    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/read")
        ->assertSuccessful();

    // Write bloqué avec 403
    $this->actingAs($user)
        ->postJson("/test-locks/{$residence->id}/write")
        ->assertForbidden();
});

test('verrou 1: suspended license blocks both read and write with 403', function () {
    $residence = Residence::factory()->create();
    License::factory()->suspended()->create(['residence_id' => $residence->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/read")
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson("/test-locks/{$residence->id}/write")
        ->assertForbidden();
});

test('verrou 1: platform admin bypasses lock 1 even when license is suspended', function () {
    $residence = Residence::factory()->create();
    License::factory()->suspended()->create(['residence_id' => $residence->id]);
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->getJson("/test-locks/{$residence->id}/read")
        ->assertSuccessful();

    $this->actingAs($admin)
        ->postJson("/test-locks/{$residence->id}/write")
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| Verrou 2: Rattachement temporel actif (CheckResidenceAttachment)
|--------------------------------------------------------------------------
*/

test('verrou 2: platform admin bypasses lock 2', function () {
    $residence = Residence::factory()->create();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->getJson("/test-locks/{$residence->id}/attachment")
        ->assertSuccessful();
});

test('verrou 2: accredited syndic collaborator has access', function () {
    $residence = Residence::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $user = User::factory()->create();

    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    SyndicCompanyUser::create([
        'syndic_company_id' => $syndic->id,
        'user_id' => $user->id,
        'role' => SyndicRole::Gestionnaire,
    ]);

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/attachment")
        ->assertSuccessful();
});

test('verrou 2: collaborator of revoked syndic company is denied with 403', function () {
    $residence = Residence::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $user = User::factory()->create();

    ResidenceAccess::factory()->revoked()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    SyndicCompanyUser::create([
        'syndic_company_id' => $syndic->id,
        'user_id' => $user->id,
        'role' => SyndicRole::Gerant,
    ]);

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/attachment")
        ->assertForbidden();
});

test('verrou 2: active council role grants access', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();

    ResidenceRole::factory()->president()->create([
        'residence_id' => $residence->id,
        'user_id' => $user->id,
        'started_on' => now()->subMonths(2),
        'ended_on' => now()->addMonths(10),
    ]);

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/attachment")
        ->assertSuccessful();
});

test('verrou 2: expired council role is denied with 403 without any cron job', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();

    ResidenceRole::factory()->create([
        'residence_id' => $residence->id,
        'user_id' => $user->id,
        'role' => ResidenceRoleType::MembreConseil,
        'started_on' => now()->subYear(),
        'ended_on' => now()->subDay(), // Expiré hier
    ]);

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/attachment")
        ->assertForbidden();
});

test('verrou 2: unattached user is denied with 403', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/test-locks/{$residence->id}/attachment")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Verrou 3: Matrice de droits 02 §4 (Laravel Policies + Gates)
|--------------------------------------------------------------------------
*/

test('matrice 02 §4: residence and license rights', function () {
    $residence = Residence::factory()->create();
    $license = License::factory()->active()->create(['residence_id' => $residence->id]);

    $admin = User::factory()->platformAdmin()->create();

    $syndic = SyndicCompany::factory()->create();
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $gerant = User::factory()->create();
    SyndicCompanyUser::create(['syndic_company_id' => $syndic->id, 'user_id' => $gerant->id, 'role' => SyndicRole::Gerant]);

    $gestionnaire = User::factory()->create();
    SyndicCompanyUser::create(['syndic_company_id' => $syndic->id, 'user_id' => $gestionnaire->id, 'role' => SyndicRole::Gestionnaire]);

    $presidentCS = User::factory()->create();
    ResidenceRole::factory()->president()->create(['residence_id' => $residence->id, 'user_id' => $presidentCS->id]);

    $stranger = User::factory()->create();

    // Admin : CRUD
    expect(Gate::forUser($admin)->allows('view', $residence))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $residence))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $residence))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $license))->toBeTrue();

    // Gérant : R (lecture seule sur résidence et licence)
    expect(Gate::forUser($gerant)->allows('view', $residence))->toBeTrue();
    expect(Gate::forUser($gerant)->allows('view', $license))->toBeTrue();
    expect(Gate::forUser($gerant)->allows('update', $residence))->toBeFalse();
    expect(Gate::forUser($gerant)->allows('update', $license))->toBeFalse();

    // Gestionnaire : R
    expect(Gate::forUser($gestionnaire)->allows('view', $residence))->toBeTrue();
    expect(Gate::forUser($gestionnaire)->allows('update', $residence))->toBeFalse();

    // Président CS : R
    expect(Gate::forUser($presidentCS)->allows('view', $residence))->toBeTrue();
    expect(Gate::forUser($presidentCS)->allows('update', $residence))->toBeFalse();

    // Stranger : non autorisé
    expect(Gate::forUser($stranger)->allows('view', $residence))->toBeFalse();
});

test('matrice 02 §4: accreditation syndic is CRUD for admin only', function () {
    $residence = Residence::factory()->create();
    $access = ResidenceAccess::factory()->active()->create(['residence_id' => $residence->id]);

    $admin = User::factory()->platformAdmin()->create();
    $syndic = $access->syndicCompany;

    $gerant = User::factory()->create();
    SyndicCompanyUser::create(['syndic_company_id' => $syndic->id, 'user_id' => $gerant->id, 'role' => SyndicRole::Gerant]);

    $presidentCS = User::factory()->create();
    ResidenceRole::factory()->president()->create(['residence_id' => $residence->id, 'user_id' => $presidentCS->id]);

    // Admin peut gérer
    expect(Gate::forUser($admin)->allows('view', $access))->toBeTrue();
    expect(Gate::forUser($admin)->allows('grant', ResidenceAccess::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('revoke', $access))->toBeTrue();

    // Gérant et Président CS : interdits (matrice 02 §4 : « — »)
    expect(Gate::forUser($gerant)->allows('view', $access))->toBeFalse();
    expect(Gate::forUser($gerant)->allows('grant', ResidenceAccess::class))->toBeFalse();
    expect(Gate::forUser($presidentCS)->allows('view', $access))->toBeFalse();
    expect(Gate::forUser($presidentCS)->allows('revoke', $access))->toBeFalse();
});

test('matrice 02 §4: journal audit access (gestionnaire denied, others allowed)', function () {
    $residence = Residence::factory()->create();
    $auditLog = AuditLog::factory()->create(['residence_id' => $residence->id]);

    $admin = User::factory()->platformAdmin()->create();

    $syndic = SyndicCompany::factory()->create();
    ResidenceAccess::factory()->active()->create([
        'residence_id' => $residence->id,
        'syndic_company_id' => $syndic->id,
    ]);

    $gerant = User::factory()->create();
    SyndicCompanyUser::create(['syndic_company_id' => $syndic->id, 'user_id' => $gerant->id, 'role' => SyndicRole::Gerant]);

    $gestionnaire = User::factory()->create();
    SyndicCompanyUser::create(['syndic_company_id' => $syndic->id, 'user_id' => $gestionnaire->id, 'role' => SyndicRole::Gestionnaire]);

    $comptable = User::factory()->create();
    SyndicCompanyUser::create(['syndic_company_id' => $syndic->id, 'user_id' => $comptable->id, 'role' => SyndicRole::Comptable]);

    $presidentCS = User::factory()->create();
    ResidenceRole::factory()->president()->create(['residence_id' => $residence->id, 'user_id' => $presidentCS->id]);

    $membreCS = User::factory()->create();
    ResidenceRole::factory()->membre()->create(['residence_id' => $residence->id, 'user_id' => $membreCS->id]);

    // Admin : R
    expect(Gate::forUser($admin)->allows('view', $auditLog))->toBeTrue();

    // Gérant : R
    expect(Gate::forUser($gerant)->allows('view', $auditLog))->toBeTrue();

    // Gestionnaire : — (INTERDIT selon la matrice 02 §4 !)
    expect(Gate::forUser($gestionnaire)->allows('view', $auditLog))->toBeFalse();

    // Comptable : R
    expect(Gate::forUser($comptable)->allows('view', $auditLog))->toBeTrue();

    // Président CS : R
    expect(Gate::forUser($presidentCS)->allows('view', $auditLog))->toBeTrue();

    // Membre CS : R
    expect(Gate::forUser($membreCS)->allows('view', $auditLog))->toBeTrue();
});

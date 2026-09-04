<?php

use App\Actions\License\CreateLicenseAction;
use App\Actions\License\ReactivateLicenseAction;
use App\Actions\License\RenewLicenseAction;
use App\Actions\License\SuspendLicenseAction;
use App\Actions\ResidenceAccess\GrantResidenceAccessAction;
use App\Actions\ResidenceAccess\RevokeResidenceAccessAction;
use App\Enums\LicenseEventType;
use App\Enums\LicenseStatus;
use App\Enums\ResidenceAccessStatus;
use App\Exceptions\ExportGenerationFailedException;
use App\Exceptions\InvalidLicenseStateException;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\SyndicCompany;
use App\Models\User;
use App\Services\ExitExportService;
use Illuminate\Auth\Access\AuthorizationException;

test('license creation initializes active state with event and audit log', function () {
    $admin = User::factory()->platformAdmin()->create();
    $residence = Residence::factory()->create();

    $action = app(CreateLicenseAction::class);
    $license = $action->handle($residence, [
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->addYear()->toDateString(),
        'grace_days' => 30,
    ], $admin);

    expect($license->status)->toBe(LicenseStatus::Active);
    expect($license->residence_id)->toBe($residence->id);

    // Vérifie license_events
    $event = LicenseEvent::where('license_id', $license->id)->latest()->first();
    expect($event)->not->toBeNull();
    expect($event->type)->toBe(LicenseEventType::Created);
    expect($event->actor_user_id)->toBe($admin->id);

    // Vérifie audit_logs
    $audit = AuditLog::where('auditable_id', $license->id)
        ->where('auditable_type', License::class)
        ->latest('id')
        ->first();
    expect($audit)->not->toBeNull();
    expect($audit->action)->toBe('license.created');
});

test('license transitions active -> grace when ends_on is exceeded', function () {
    $residence = Residence::factory()->create();
    $license = License::factory()->create([
        'residence_id' => $residence->id,
        'status' => LicenseStatus::Active,
        'starts_on' => now()->subYear(),
        'ends_on' => now()->subDay(), // Expirée hier
        'grace_days' => 30,
    ]);

    $this->artisan('licenses:check-expiration')->assertSuccessful();

    $license->refresh();
    expect($license->status)->toBe(LicenseStatus::Grace);

    $event = LicenseEvent::where('license_id', $license->id)->latest('id')->first();
    expect($event->type)->toBe(LicenseEventType::Expired);
    expect($event->actor_user_id)->toBeNull(); // Déclencheur système
});

test('license transitions grace -> read_only when ends_on + grace_days is exceeded', function () {
    $residence = Residence::factory()->create();
    $license = License::factory()->create([
        'residence_id' => $residence->id,
        'status' => LicenseStatus::Grace,
        'starts_on' => now()->subYear(),
        'ends_on' => now()->subDays(35), // Grâce de 30j dépassée
        'grace_days' => 30,
    ]);

    $this->artisan('licenses:check-expiration')->assertSuccessful();

    $license->refresh();
    expect($license->status)->toBe(LicenseStatus::ReadOnly);

    $event = LicenseEvent::where('license_id', $license->id)->latest('id')->first();
    expect($event->type)->toBe(LicenseEventType::Expired);
});

test('admin can renew license from grace or read_only back to active', function () {
    $admin = User::factory()->platformAdmin()->create();
    $license = License::factory()->readOnly()->create();

    $action = app(RenewLicenseAction::class);
    $action->handle($license, now()->addYear(), $admin, 'Renouvellement annuel après paiement');

    $license->refresh();
    expect($license->status)->toBe(LicenseStatus::Active);

    $event = LicenseEvent::where('license_id', $license->id)->latest('id')->first();
    expect($event->type)->toBe(LicenseEventType::Renewed);
    expect($event->actor_user_id)->toBe($admin->id);
});

test('non admin cannot renew license', function () {
    $user = User::factory()->create(); // not platform admin
    $license = License::factory()->readOnly()->create();

    $action = app(RenewLicenseAction::class);
    expect(fn () => $action->handle($license, now()->addYear(), $user, 'Tentative non admin'))
        ->toThrow(AuthorizationException::class);
});

test('admin can suspend license with mandatory motif (minimum 10 characters)', function () {
    $admin = User::factory()->platformAdmin()->create();
    $license = License::factory()->active()->create();

    $action = app(SuspendLicenseAction::class);

    // Motif trop court (< 10 caractères)
    expect(fn () => $action->handle($license, $admin, 'Court'))
        ->toThrow(InvalidTransitionMotifException::class);

    // Motif valide
    $action->handle($license, $admin, 'Suspension suite à contentieux grave', 'docs/suspension.pdf');

    $license->refresh();
    expect($license->status)->toBe(LicenseStatus::Suspended);

    $event = LicenseEvent::where('license_id', $license->id)->latest('id')->first();
    expect($event->type)->toBe(LicenseEventType::Suspended);

    $audit = AuditLog::where('action', 'license.suspended')->latest('id')->first();
    expect($audit)->not->toBeNull();
    expect($audit->motif)->toBe('Suspension suite à contentieux grave');
});

test('admin can reactivate suspended license with motif', function () {
    $admin = User::factory()->platformAdmin()->create();
    $license = License::factory()->suspended()->create([
        'ends_on' => now()->addMonths(6),
    ]);

    $action = app(ReactivateLicenseAction::class);

    // Motif trop court
    expect(fn () => $action->handle($license, $admin, 'OK'))
        ->toThrow(InvalidTransitionMotifException::class);

    // Motif valide
    $action->handle($license, $admin, 'Résolution du contentieux et régularisation');

    $license->refresh();
    expect($license->status)->toBe(LicenseStatus::Active);

    $event = LicenseEvent::where('license_id', $license->id)->latest('id')->first();
    expect($event->type)->toBe(LicenseEventType::Reactivated);
});

test('syndic company accreditation cannot be granted if license is suspended', function () {
    $admin = User::factory()->platformAdmin()->create();
    $residence = Residence::factory()->create();
    License::factory()->suspended()->create(['residence_id' => $residence->id]);
    $syndic = SyndicCompany::factory()->create();

    $action = app(GrantResidenceAccessAction::class);
    expect(fn () => $action->handle($residence, $syndic, $admin))
        ->toThrow(InvalidLicenseStateException::class);
});

test('syndic company accreditation revocation requires motif, piece, and is irreversible with blocking export', function () {
    $admin = User::factory()->platformAdmin()->create();
    $access = ResidenceAccess::factory()->active()->create();

    $action = app(RevokeResidenceAccessAction::class);

    // Motif trop court
    expect(fn () => $action->handle($access, $admin, 'Court', 'doc.pdf', now()))
        ->toThrow(InvalidTransitionMotifException::class);

    // Pièce justificative vide
    expect(fn () => $action->handle($access, $admin, 'Révocation suite à assemblée générale', '', now()))
        ->toThrow(InvalidArgumentException::class);

    // Révocation valide
    $action->handle(
        $access,
        $admin,
        'Changement de syndic voté en assemblée générale du 15/01',
        'documents/ag/pv_revocation_1501.pdf',
        now(),
    );

    $access->refresh();
    expect($access->status)->toBe(ResidenceAccessStatus::Revoked);
    expect($access->export_generated_at)->not->toBeNull();
    expect($access->revoked_by_admin_id)->toBe($admin->id);

    // Irréversible : révoquer à nouveau échoue
    expect(fn () => $action->handle(
        $access,
        $admin,
        'Deuxième tentative de révocation',
        'documents/ag/pv2.pdf',
        now(),
    ))->toThrow(InvalidArgumentException::class);
});

test('revocation fails and rolls back if exit export cannot be generated', function () {
    $admin = User::factory()->platformAdmin()->create();
    $access = ResidenceAccess::factory()->active()->create();

    // Mock ExitExportService to throw exception
    $failingExportService = Mockery::mock(ExitExportService::class);
    $failingExportService->shouldReceive('generate')
        ->andThrow(new ExportGenerationFailedException('Erreur export'));

    $action = new RevokeResidenceAccessAction($failingExportService);

    expect(fn () => $action->handle(
        $access,
        $admin,
        'Révocation avec export bloquant qui échoue',
        'doc.pdf',
        now(),
    ))->toThrow(ExportGenerationFailedException::class);

    $access->refresh();
    expect($access->status)->toBe(ResidenceAccessStatus::Active);
    expect($access->export_generated_at)->toBeNull();
});

test('revocation accepts effective date as string without error', function () {
    $admin = User::factory()->platformAdmin()->create();
    $access = ResidenceAccess::factory()->active()->create();

    $action = app(RevokeResidenceAccessAction::class);
    $action->handle(
        $access,
        $admin,
        'Révocation avec date passée sous forme de chaîne de caractères',
        'documents/ag/pv_chaine.pdf',
        '2026-09-04 15:30:00',
    );

    $access->refresh();
    expect($access->status)->toBe(ResidenceAccessStatus::Revoked);
    expect($access->revoked_at->format('Y-m-d H:i:s'))->toBe('2026-09-04 15:30:00');
});

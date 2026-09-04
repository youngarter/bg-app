<?php

use App\Enums\LicenseEventType;
use App\Exceptions\ImmutableRecordException;
use App\Models\AuditLog;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('audit_logs can be inserted', function () {
    $residence = Residence::factory()->create();
    $user = User::factory()->create();

    $log = AuditLog::create([
        'residence_id' => $residence->id,
        'actor_user_id' => $user->id,
        'action' => 'test.action',
        'auditable_type' => Residence::class,
        'auditable_id' => $residence->id,
        'motif' => 'Motif de test pour insertion journal',
        'ip' => '127.0.0.1',
        'user_agent' => 'PestTest',
    ]);

    expect($log->exists)->toBeTrue();
    expect(AuditLog::count())->toBe(1);
});

test('audit_logs cannot be updated via Eloquent', function () {
    $log = AuditLog::factory()->create([
        'motif' => 'Motif initial',
    ]);

    expect(fn () => $log->update(['motif' => 'Nouveau motif']))
        ->toThrow(ImmutableRecordException::class);

    expect(fn () => $log->save())
        ->toThrow(ImmutableRecordException::class);
});

test('audit_logs cannot be deleted via Eloquent', function () {
    $log = AuditLog::factory()->create();

    expect(fn () => $log->delete())
        ->toThrow(ImmutableRecordException::class);
});

test('audit_logs cannot be updated via direct SQL (PostgreSQL trigger)', function () {
    $log = AuditLog::factory()->create();

    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->update(['motif' => 'SQL update bypass']))
        ->toThrow(QueryException::class);
});

test('audit_logs cannot be deleted via direct SQL (PostgreSQL trigger)', function () {
    $log = AuditLog::factory()->create();

    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->delete())
        ->toThrow(QueryException::class);
});

test('license_events can be inserted', function () {
    $license = License::factory()->create();
    $admin = User::factory()->platformAdmin()->create();

    $event = LicenseEvent::create([
        'license_id' => $license->id,
        'type' => LicenseEventType::Created,
        'effective_at' => now(),
        'actor_user_id' => $admin->id,
        'note' => 'Création initiale',
    ]);

    expect($event->exists)->toBeTrue();
    expect(LicenseEvent::count())->toBe(1);
});

test('license_events cannot be updated via Eloquent', function () {
    $event = LicenseEvent::factory()->create();

    expect(fn () => $event->update(['note' => 'Note modifiée']))
        ->toThrow(ImmutableRecordException::class);

    expect(fn () => $event->save())
        ->toThrow(ImmutableRecordException::class);
});

test('license_events cannot be deleted via Eloquent', function () {
    $event = LicenseEvent::factory()->create();

    expect(fn () => $event->delete())
        ->toThrow(ImmutableRecordException::class);
});

test('license_events cannot be updated via direct SQL (PostgreSQL trigger)', function () {
    $event = LicenseEvent::factory()->create();

    expect(fn () => DB::table('license_events')->where('id', $event->id)->update(['note' => 'SQL bypass']))
        ->toThrow(QueryException::class);
});

test('license_events cannot be deleted via direct SQL (PostgreSQL trigger)', function () {
    $event = LicenseEvent::factory()->create();

    expect(fn () => DB::table('license_events')->where('id', $event->id)->delete())
        ->toThrow(QueryException::class);
});

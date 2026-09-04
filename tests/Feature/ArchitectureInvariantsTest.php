<?php

use App\Exceptions\ImmutableRecordException;
use App\Models\AuditLog;
use App\Models\Concerns\BelongsToResidence;
use App\Models\Delegation;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Lot;
use App\Models\LotAccount;
use App\Models\LotMutation;
use App\Models\LotOwnership;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\ResidenceRole;
use App\Support\ResidenceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

test('architecture invariant 4: AuditLog and LicenseEvent prevent update and delete', function () {
    $auditLog = AuditLog::factory()->create();
    expect(fn () => $auditLog->update(['motif' => 'new']))
        ->toThrow(ImmutableRecordException::class);
    expect(fn () => $auditLog->delete())
        ->toThrow(ImmutableRecordException::class);

    $event = LicenseEvent::factory()->create();
    expect(fn () => $event->update(['note' => 'new']))
        ->toThrow(ImmutableRecordException::class);
    expect(fn () => $event->delete())
        ->toThrow(ImmutableRecordException::class);
});

test('architecture invariant 5: every model whose table has residence_id uses BelongsToResidence trait', function () {
    $modelFiles = glob(app_path('Models/*.php'));
    $scopedModelsFound = [];

    foreach ($modelFiles as $file) {
        $className = 'App\\Models\\'.basename($file, '.php');

        if (! class_exists($className)) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
            continue;
        }

        /** @var Model $instance */
        $instance = new $className;
        $table = $instance->getTable();

        if (Schema::hasColumn($table, 'residence_id')) {
            $scopedModelsFound[] = $className;
            $traits = class_uses_recursive($className);

            expect(in_array(BelongsToResidence::class, $traits, true))
                ->toBeTrue("Le modèle {$className} possède une colonne residence_id sur la table '{$table}' et DOIT utiliser le trait BelongsToResidence.");
        }
    }

    // Vérifie qu'on a bien testé tous les modèles concernés
    expect($scopedModelsFound)->toContain(
        License::class,
        ResidenceAccess::class,
        ResidenceRole::class,
        AuditLog::class,
        Lot::class,
        Owner::class,
        LotOwnership::class,
        LotAccount::class,
        LotMutation::class,
        Delegation::class,
    );
});

test('architecture invariant 13 §2 rule 3: no direct assignment to status or state outside App\\Actions', function () {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $violations = [];
    $actionsPath = realpath(app_path('Actions'));

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();

        // Les Actions ont le droit d'effectuer les transitions d'état
        if (str_starts_with($filePath, $actionsPath)) {
            continue;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            // Détecte ->status = ou ->state = en excluant les comparaisons (==, ===)
            if (preg_match('/->(status|state)\s*=\s*[^=]/', $line)) {
                $relativePath = str_replace(base_path().'/', '', $filePath);
                $violations[] = "{$relativePath}:".($lineNumber + 1).' -> '.trim($line);
            }
        }
    }

    expect($violations)->toBeEmpty(
        "Assignation directe d'un champ d'état (status ou state) détectée hors de App\\Actions :\n".implode("\n", $violations)
    );
});

test('architecture invariant 5: business models have residence scoping in queries', function () {
    $residence1 = Residence::factory()->create();
    $residence2 = Residence::factory()->create();

    $license1 = License::factory()->create(['residence_id' => $residence1->id]);
    $license2 = License::factory()->create(['residence_id' => $residence2->id]);

    $role1 = ResidenceRole::factory()->create(['residence_id' => $residence1->id]);
    $role2 = ResidenceRole::factory()->create(['residence_id' => $residence2->id]);

    $access1 = ResidenceAccess::factory()->create(['residence_id' => $residence1->id]);
    $access2 = ResidenceAccess::factory()->create(['residence_id' => $residence2->id]);

    $log1 = AuditLog::factory()->create(['residence_id' => $residence1->id]);
    $log2 = AuditLog::factory()->create(['residence_id' => $residence2->id]);

    // Sans contexte de résidence (ex. admin global)
    ResidenceContext::forget();
    expect(License::count())->toBe(2);
    expect(ResidenceRole::count())->toBe(2);
    expect(ResidenceAccess::count())->toBe(2);
    expect(AuditLog::count())->toBe(2);

    // Avec contexte de résidence 1
    ResidenceContext::set($residence1);
    expect(License::count())->toBe(1);
    expect(License::first()->id)->toBe($license1->id);

    expect(ResidenceRole::count())->toBe(1);
    expect(ResidenceRole::first()->id)->toBe($role1->id);

    expect(ResidenceAccess::count())->toBe(1);
    expect(ResidenceAccess::first()->id)->toBe($access1->id);

    expect(AuditLog::count())->toBe(1);
    expect(AuditLog::first()->id)->toBe($log1->id);

    // Avec contexte de résidence 2
    ResidenceContext::set($residence2);
    expect(License::count())->toBe(1);
    expect(License::first()->id)->toBe($license2->id);

    expect(ResidenceRole::count())->toBe(1);
    expect(ResidenceRole::first()->id)->toBe($role2->id);

    expect(ResidenceAccess::count())->toBe(1);
    expect(ResidenceAccess::first()->id)->toBe($access2->id);

    expect(AuditLog::count())->toBe(1);
    expect(AuditLog::first()->id)->toBe($log2->id);

    ResidenceContext::forget();
});

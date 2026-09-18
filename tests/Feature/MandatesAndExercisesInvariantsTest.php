<?php

use App\Actions\Exercise\CreateExerciseAction;
use App\Actions\Exercise\FinalizeExerciseClosingAction;
use App\Actions\Exercise\ReopenExerciseAction;
use App\Actions\Exercise\StartExerciseClosingAction;
use App\Actions\Mandate\ActivateMandateAction;
use App\Actions\Mandate\CloseMandateAction;
use App\Actions\Mandate\CreateMandateAction;
use App\Actions\Mandate\ResumeMandateAction;
use App\Actions\Mandate\SuspendMandateAction;
use App\Actions\Mandate\TerminateMandateAction;
use App\Enums\ExerciseState;
use App\Enums\MandateState;
use App\Exceptions\InvalidStateException;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\Exercise;
use App\Models\Mandate;
use App\Models\Residence;
use App\Models\SyndicCompany;
use App\Models\User;
use App\Support\ResidenceContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    ResidenceContext::forget();
});

/*
|--------------------------------------------------------------------------
| Invariant T1 : Les exercices d'une résidence ne se chevauchent jamais
|--------------------------------------------------------------------------
*/

test('invariant T1: exercises of the same residence cannot overlap (action rejection)', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2026-12-31',
    ]);

    $createAction = app(CreateExerciseAction::class);

    // Exercice 1 : année 2025
    $ex1 = $createAction->handle(
        $residence,
        $mandate,
        '2025',
        '2025-01-01',
        '2025-12-31',
        open: false // fermé pour ne pas violer T3
    );
    expect($ex1->exists)->toBeTrue();

    // Exercice chevauchant (ex: démarre en juin 2025)
    expect(fn () => $createAction->handle(
        $residence,
        $mandate,
        '2025-OVERLAP',
        '2025-06-01',
        '2026-05-31',
        open: false
    ))->toThrow(InvalidArgumentException::class, 'chevauchent');
});

test('invariant T1: consecutive exercises do not overlap and are accepted', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2025-12-31',
    ]);

    $createAction = app(CreateExerciseAction::class);

    // Exercice 2025-A : 01/01 -> 15/08
    $exA = $createAction->handle($residence, $mandate, '2025-A', '2025-01-01', '2025-08-15', open: false);
    // Exercice 2025-B : 16/08 -> 31/12
    $exB = $createAction->handle($residence, $mandate, '2025-B', '2025-08-16', '2025-12-31', open: false);

    expect($exA->exists)->toBeTrue();
    expect($exB->exists)->toBeTrue();
});

test('invariant T1: postgresql exclusion constraint rejects overlapping exercises', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2026-12-31',
    ]);

    Exercise::create([
        'residence_id' => $residence->id,
        'mandate_id' => $mandate->id,
        'label' => '2025',
        'started_on' => '2025-01-01',
        'ended_on' => '2025-12-31',
        'state' => ExerciseState::Closed,
    ]);

    // Insertion directe outrepassant l'Action
    expect(fn () => DB::table('exercises')->insert([
        'residence_id' => $residence->id,
        'mandate_id' => $mandate->id,
        'label' => '2025-PIRATE',
        'started_on' => '2025-06-01',
        'ended_on' => '2026-05-31',
        'state' => ExerciseState::Closed->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| Invariant T2 : [exercise.started_on, ended_on] ⊆ [mandate.started_on, ended_on]
|--------------------------------------------------------------------------
*/

test('invariant T2: exercise period must be contained within mandate period', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2025-12-31',
    ]);

    $createAction = app(CreateExerciseAction::class);

    // Début avant le mandat
    expect(fn () => $createAction->handle(
        $residence,
        $mandate,
        '2024-2025',
        '2024-12-31',
        '2025-12-31',
        open: true
    ))->toThrow(InvalidArgumentException::class, 'entièrement comprise');

    // Fin après le mandat
    expect(fn () => $createAction->handle(
        $residence,
        $mandate,
        '2025-2026',
        '2025-01-01',
        '2026-01-01',
        open: true
    ))->toThrow(InvalidArgumentException::class, 'entièrement comprise');
});

/*
|--------------------------------------------------------------------------
| Invariant T3 : Un seul exercice vivant (open ou closing) par résidence à la fois
|--------------------------------------------------------------------------
*/

test('invariant T3: only one active exercise (open or closing) per residence at a time', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2026-12-31',
    ]);

    $createAction = app(CreateExerciseAction::class);

    // Premier exercice ouvert
    $ex1 = $createAction->handle($residence, $mandate, '2025', '2025-01-01', '2025-12-31', open: true);
    expect($ex1->isOpen())->toBeTrue();

    // Deuxième exercice en open pour la même résidence doit échouer
    expect(fn () => $createAction->handle(
        $residence,
        $mandate,
        '2026',
        '2026-01-01',
        '2026-12-31',
        open: true
    ))->toThrow(InvalidStateException::class, 'déjà en cours');
});

test('invariant T3: distinct residences can each have an open exercise simultaneously', function () {
    $residenceA = Residence::factory()->create();
    $residenceB = Residence::factory()->create();

    $mandateA = Mandate::factory()->create(['residence_id' => $residenceA->id, 'started_on' => '2025-01-01', 'ended_on' => '2025-12-31']);
    $mandateB = Mandate::factory()->create(['residence_id' => $residenceB->id, 'started_on' => '2025-01-01', 'ended_on' => '2025-12-31']);

    $createAction = app(CreateExerciseAction::class);

    $exA = $createAction->handle($residenceA, $mandateA, '2025', '2025-01-01', '2025-12-31', open: true);
    $exB = $createAction->handle($residenceB, $mandateB, '2025', '2025-01-01', '2025-12-31', open: true);

    expect($exA->isOpen())->toBeTrue();
    expect($exB->isOpen())->toBeTrue();
});

test('invariant T3: postgresql partial unique index rejects two open or closing exercises', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2026-12-31',
    ]);

    Exercise::create([
        'residence_id' => $residence->id,
        'mandate_id' => $mandate->id,
        'label' => '2025',
        'started_on' => '2025-01-01',
        'ended_on' => '2025-12-31',
        'state' => ExerciseState::Open,
    ]);

    // Insertion directe d'un second exercice en état closing
    expect(fn () => DB::table('exercises')->insert([
        'residence_id' => $residence->id,
        'mandate_id' => $mandate->id,
        'label' => '2026',
        'started_on' => '2026-01-01',
        'ended_on' => '2026-12-31',
        'state' => ExerciseState::Closing->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| Scénario de réouverture (Correction 1)
|--------------------------------------------------------------------------
*/

test('reopening scenario: 2025 closing, 2026 cannot open, controls fail, 2025 reopens successfully', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2026-12-31',
    ]);

    $createAction = app(CreateExerciseAction::class);
    $startClosingAction = app(StartExerciseClosingAction::class);
    $reopenAction = app(ReopenExerciseAction::class);

    // 1. Exercice 2025 ouvert
    $ex2025 = $createAction->handle($residence, $mandate, '2025', '2025-01-01', '2025-12-31', open: true);

    // 2. Bascule en cours de clôture
    $startClosingAction->handle($ex2025);
    expect($ex2025->isClosing())->toBeTrue();

    // 3. Tentative d'ouvrir 2026 : doit être bloquée car 2025 est encore vivant (en closing)
    expect(fn () => $createAction->handle(
        $residence,
        $mandate,
        '2026',
        '2026-01-01',
        '2026-12-31',
        open: true
    ))->toThrow(InvalidStateException::class, 'déjà en cours');

    // 4. Échec des contrôles : réouverture de 2025 (closing -> open)
    $reopened = $reopenAction->handle($ex2025, motif: 'Écart d\'imputation détecté avant approbation');
    expect($reopened->isOpen())->toBeTrue();
    expect($reopened->id)->toBe($ex2025->id);
});

/*
|--------------------------------------------------------------------------
| Invariant T4 : Chaque exercice appartient à exactement un mandat
|--------------------------------------------------------------------------
*/

test('invariant T4: each exercise belongs to exactly one mandate', function () {
    $residence = Residence::factory()->create();
    $otherResidence = Residence::factory()->create();

    $mandate = Mandate::factory()->create(['residence_id' => $residence->id, 'started_on' => '2025-01-01', 'ended_on' => '2025-12-31']);
    $otherMandate = Mandate::factory()->create(['residence_id' => $otherResidence->id, 'started_on' => '2025-01-01', 'ended_on' => '2025-12-31']);

    $createAction = app(CreateExerciseAction::class);

    // Mandat d'une autre résidence rejeté
    expect(fn () => $createAction->handle(
        $residence,
        $otherMandate,
        '2025',
        '2025-01-01',
        '2025-12-31'
    ))->toThrow(InvalidArgumentException::class, 'n\'appartient pas à cette résidence');

    $exercise = $createAction->handle($residence, $mandate, '2025', '2025-01-01', '2025-12-31');
    expect($exercise->mandate_id)->toBe($mandate->id);
    expect($exercise->mandate->id)->toBe($mandate->id);
});

/*
|--------------------------------------------------------------------------
| Spécificités métier : deux mandats même année & durée libre
|--------------------------------------------------------------------------
*/

test('two mandates can exist and carry data on the same civil year with fractional exercises', function () {
    $residence = Residence::factory()->create();
    $syndicA = SyndicCompany::factory()->create();
    $syndicB = SyndicCompany::factory()->create();

    $createMandate = app(CreateMandateAction::class);
    $createExercise = app(CreateExerciseAction::class);

    // Mandat #12 : 01/01/2025 -> 15/08/2025 (clôture anticipée)
    $mandate12 = $createMandate->handle($residence, $syndicA, '2025-01-01', '2025-08-15', activate: true);
    // Exercice 2025-A
    $ex2025A = $createExercise->handle($residence, $mandate12, '2025-A', '2025-01-01', '2025-08-15', open: false);

    // Mandat #13 : 16/08/2025 -> 31/12/2026
    $mandate13 = $createMandate->handle($residence, $syndicB, '2025-08-16', '2026-12-31', activate: true);
    // Exercice 2025-B
    $ex2025B = $createExercise->handle($residence, $mandate13, '2025-B', '2025-08-16', '2025-12-31', open: true);

    expect($mandate12->exists)->toBeTrue();
    expect($mandate13->exists)->toBeTrue();
    expect($ex2025A->label)->toBe('2025-A');
    expect($ex2025B->label)->toBe('2025-B');
    expect(is_string($ex2025A->label))->toBeTrue();
});

test('mandate duration is free and not constrained to 2 years', function () {
    $residence = Residence::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $createMandate = app(CreateMandateAction::class);

    // Mandat court de 6 mois
    $m6m = $createMandate->handle($residence, $syndic, '2025-01-01', '2025-06-30');
    expect($m6m->exists)->toBeTrue();

    // Mandat d'1 an
    $m1y = $createMandate->handle($residence, $syndic, '2025-07-01', '2026-06-30');
    expect($m1y->exists)->toBeTrue();

    // Mandat de 3 ans
    $m3y = $createMandate->handle($residence, $syndic, '2026-07-01', '2029-06-30');
    expect($m3y->exists)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Transitions d'état du mandat & validation de motif
|--------------------------------------------------------------------------
*/

test('mandate state machine: draft -> active -> suspended -> active', function () {
    $residence = Residence::factory()->create();
    $syndic = SyndicCompany::factory()->create();
    $admin = User::factory()->create();

    $mandate = app(CreateMandateAction::class)->handle(
        $residence,
        $syndic,
        '2025-01-01',
        '2026-12-31',
        activate: false
    );
    expect($mandate->isDraft())->toBeTrue();

    // Activation
    app(ActivateMandateAction::class)->handle($mandate, $admin);
    expect($mandate->refresh()->isActive())->toBeTrue();

    // Suspension avec motif court (< 10 car) : refusée
    expect(fn () => app(SuspendMandateAction::class)->handle(
        $mandate,
        'Court',
        $admin
    ))->toThrow(InvalidTransitionMotifException::class);

    // Suspension valide
    app(SuspendMandateAction::class)->handle($mandate, 'Suspension suite à litige sur honoraires', $admin);
    expect($mandate->refresh()->state)->toBe(MandateState::Suspended);

    // Reprise
    app(ResumeMandateAction::class)->handle($mandate, $admin);
    expect($mandate->refresh()->isActive())->toBeTrue();
});

test('suspended mandate protects its period: activating an overlapping mandate fails', function () {
    $residence = Residence::factory()->create();
    $syndicA = SyndicCompany::factory()->create();
    $syndicB = SyndicCompany::factory()->create();
    $admin = User::factory()->create();

    $createMandate = app(CreateMandateAction::class);
    $activateAction = app(ActivateMandateAction::class);
    $suspendAction = app(SuspendMandateAction::class);

    // Mandat A : 01/01/2025 -> 31/12/2026, activé puis suspendu pour litige
    $mandateA = $createMandate->handle($residence, $syndicA, '2025-01-01', '2026-12-31', activate: true);
    $suspendAction->handle($mandateA, 'Litige sur les honoraires de gestion', $admin);
    expect($mandateA->refresh()->state)->toBe(MandateState::Suspended);

    // Mandat B : 01/06/2025 -> 30/06/2026, tentative d'activation durant la période
    $mandateB = $createMandate->handle($residence, $syndicB, '2025-06-01', '2026-06-30', activate: false);

    expect(fn () => $activateAction->handle($mandateB, $admin))
        ->toThrow(InvalidArgumentException::class, 'chevauchant');

    // Également rejeté si CreateMandateAction tente de créer directement en actif
    expect(fn () => $createMandate->handle($residence, $syndicB, '2025-06-01', '2026-06-30', activate: true))
        ->toThrow(InvalidArgumentException::class, 'chevauchant');
});

test('mandate state machine: active -> terminated requires motif and document and writes audit log', function () {
    $mandate = Mandate::factory()->active()->create();
    $user = User::factory()->create();

    // Sans document justificatif
    expect(fn () => app(TerminateMandateAction::class)->handle(
        $mandate,
        'Résiliation anticipée votée en assemblée générale',
        '',
        $user
    ))->toThrow(InvalidArgumentException::class, 'justificative');

    // Avec motif insuffisant (< 10 caractères)
    expect(fn () => app(TerminateMandateAction::class)->handle(
        $mandate,
        'motif',
        'documents/pv.pdf',
        $user
    ))->toThrow(InvalidTransitionMotifException::class);

    // Valide
    $terminated = app(TerminateMandateAction::class)->handle(
        $mandate,
        'Résiliation votée par l\'AG extraordinaire du 15 août',
        'documents/pv_ag_extraordinaire.pdf',
        $user
    );

    expect($terminated->isTerminated())->toBeTrue();
    expect($terminated->terminated_motif)->toBe('Résiliation votée par l\'AG extraordinaire du 15 août');

    // Vérifie l'écriture obligatoire dans audit_logs (11 §12.1)
    $audit = AuditLog::where('auditable_type', Mandate::class)
        ->where('auditable_id', $mandate->id)
        ->where('action', 'mandate.terminated')
        ->first();
    expect($audit)->not->toBeNull();
    expect($audit->motif)->toBe('Résiliation votée par l\'AG extraordinaire du 15 août');
});

test('mandate cannot be closed if exercises are not closed, and writes audit log when closed', function () {
    $mandate = Mandate::factory()->expired()->create();
    $user = User::factory()->create();

    // Exercice encore ouvert rattaché au mandat
    Exercise::factory()->create([
        'residence_id' => $mandate->residence_id,
        'mandate_id' => $mandate->id,
        'state' => ExerciseState::Open,
        'started_on' => $mandate->started_on,
        'ended_on' => $mandate->ended_on,
    ]);

    // Tentative de clôture bloquée
    expect(fn () => app(CloseMandateAction::class)->handle($mandate, $user))
        ->toThrow(InvalidStateException::class, 'clôturés');

    // Clôture de l'exercice
    $exercise = $mandate->exercises()->first();
    $exercise->state = ExerciseState::Closed;
    $exercise->save();

    // Clôture du mandat réussie
    $closed = app(CloseMandateAction::class)->handle($mandate, $user);
    expect($closed->isClosed())->toBeTrue();

    // Vérifie l'écriture dans audit_logs
    $audit = AuditLog::where('auditable_type', Mandate::class)
        ->where('auditable_id', $mandate->id)
        ->where('action', 'mandate.closed')
        ->first();
    expect($audit)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Commande planifiée d'expiration des mandats (Correction 2)
|--------------------------------------------------------------------------
*/

test('scheduled command mandates:check-expiration marks expired mandates', function () {
    $residence = Residence::factory()->create();
    $mandateExpired = Mandate::factory()->active()->create([
        'residence_id' => $residence->id,
        'started_on' => now()->subYears(2)->toDateString(),
        'ended_on' => now()->subDay()->toDateString(), // Échu hier
    ]);

    $mandateCurrent = Mandate::factory()->active()->create([
        'residence_id' => $residence->id,
        'started_on' => now()->toDateString(),
        'ended_on' => now()->addYear()->toDateString(), // Encore valide
    ]);

    $this->artisan('mandates:check-expiration')
        ->expectsOutputToContain('1 mandat(s) expiré(s)')
        ->assertSuccessful();

    expect($mandateExpired->refresh()->isExpired())->toBeTrue();
    expect($mandateCurrent->refresh()->isActive())->toBeTrue();
});

test('Option B: suspended mandate is not automatically expired and can be terminated by admin', function () {
    $residence = Residence::factory()->create();
    $admin = User::factory()->create();

    // Mandat suspendu dont la date de fin est dépassée
    $mandateSuspended = Mandate::factory()->suspended()->create([
        'residence_id' => $residence->id,
        'started_on' => now()->subYears(2)->toDateString(),
        'ended_on' => now()->subDay()->toDateString(),
    ]);

    // La commande planifiée ne le touche pas (seuls les mandats active expirent automatiquement)
    $this->artisan('mandates:check-expiration')
        ->expectsOutputToContain('0 mandat(s) expiré(s)')
        ->assertSuccessful();

    expect($mandateSuspended->refresh()->state)->toBe(MandateState::Suspended);

    // L'administrateur peut résilier formellement le mandat suspendu (suspended -> terminated)
    $terminated = app(TerminateMandateAction::class)->handle(
        $mandateSuspended,
        'Résiliation formelle du mandat suspendu après assemblée générale',
        'documents/pv_resolution_mandat.pdf',
        $admin
    );
    expect($terminated->isTerminated())->toBeTrue();

    // Le mandat résilié peut désormais être clôturé une fois ses exercices clos (terminated -> closed)
    $closed = app(CloseMandateAction::class)->handle($terminated, $admin);
    expect($closed->isClosed())->toBeTrue();
});

test('Option B: suspended mandate can be resumed and then expired by scheduled command', function () {
    $residence = Residence::factory()->create();
    $admin = User::factory()->create();

    // Mandat suspendu dont la date de fin est dépassée
    $mandateSuspended = Mandate::factory()->suspended()->create([
        'residence_id' => $residence->id,
        'started_on' => now()->subYears(2)->toDateString(),
        'ended_on' => now()->subDay()->toDateString(),
    ]);

    // Reprise du mandat (suspended -> active)
    app(ResumeMandateAction::class)->handle($mandateSuspended, $admin);
    expect($mandateSuspended->refresh()->isActive())->toBeTrue();

    // La commande planifiée le fait alors expirer immédiatement (active -> expired)
    $this->artisan('mandates:check-expiration')
        ->expectsOutputToContain('1 mandat(s) expiré(s)')
        ->assertSuccessful();

    expect($mandateSuspended->refresh()->isExpired())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Clôture d'exercice & audit_logs
|--------------------------------------------------------------------------
*/

test('finalize exercise closing marks closed, records timestamps and writes audit log', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->create([
        'residence_id' => $residence->id,
        'started_on' => '2025-01-01',
        'ended_on' => '2025-12-31',
    ]);
    $user = User::factory()->create();

    $exercise = Exercise::factory()->closing()->create([
        'residence_id' => $residence->id,
        'mandate_id' => $mandate->id,
        'label' => '2025',
        'started_on' => '2025-01-01',
        'ended_on' => '2025-12-31',
    ]);

    $finalized = app(FinalizeExerciseClosingAction::class)->handle($exercise, $user);

    expect($finalized->isClosed())->toBeTrue();
    expect($finalized->closed_at)->not->toBeNull();
    expect($finalized->closed_by_user_id)->toBe($user->id);

    // Audit log
    $audit = AuditLog::where('auditable_type', Exercise::class)
        ->where('auditable_id', $exercise->id)
        ->where('action', 'exercise.closed')
        ->first();
    expect($audit)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Tests skippés pour les contrôles futurs de clôture d'exercice (04 §4 & 11 §4)
|--------------------------------------------------------------------------
*/

test('closing checks no draft or submitted fund calls exist (todo Étape 6)', function () {
    // Sera implémenté à l'Étape 6 avec fund_calls
})->skip('Contrôle bloquant d\'appels non émis à brancher à l\'Étape 6');

test('closing checks no unconfirmed payments exist (todo Étape 7)', function () {
    // Sera implémenté à l'Étape 7 avec payments
})->skip('Contrôle bloquant de paiements non confirmés à brancher à l\'Étape 7');

test('closing checks no pending payment allocations exist (todo Étape 7)', function () {
    // Sera implémenté à l'Étape 7 avec payment_allocations
})->skip('Contrôle bloquant d\'imputations en attente à brancher à l\'Étape 7');

test('closing checks ledger lines balanced on every entry (todo Étape 4)', function () {
    // Sera implémenté à l'Étape 4 avec le grand livre
})->skip('Contrôle d\'équilibre des pièces à brancher à l\'Étape 4');

test('closing checks lot account balances match ledger projection (todo Étape 4)', function () {
    // Sera implémenté à l'Étape 4 avec les comptes auxiliaires et le grand livre
})->skip('Contrôle de cohérence soldes lots = ledger à brancher à l\'Étape 4');

test('closing generates closing entries 711x to 119x (todo Étape 4)', function () {
    // Sera implémenté à l'Étape 4 avec les écritures comptables
})->skip('Génération des écritures de solde produits vers report à nouveau à brancher à l\'Étape 4');

test('closing generates a-nouveaux for balance sheet accounts on next exercise (todo Étape 4)', function () {
    // Sera implémenté à l'Étape 4 avec les à-nouveaux
})->skip('Génération des à-nouveaux de bilan à brancher à l\'Étape 4');

test('closing requires syndic council approval (todo Étape 5)', function () {
    // Sera implémenté à l'Étape 5 avec approval_requests
})->skip('Contrôle bloquant d\'approbation du conseil syndical à brancher à l\'Étape 5');

test('invariant T2: postgresql trigger rejects an exercise outside its mandate even when the Action is bypassed', function () {
    $residence = Residence::factory()->create();
    $mandate = Mandate::factory()->active()->create([
        'residence_id' => $residence->id,
        'started_on' => '2026-06-01',
        'ended_on' => '2028-05-31',
    ]);

    // Ecriture directe en base, sans passer par CreateExerciseAction :
    // c'est le chemin qu'empruntaient les seeders et qui avait produit la violation.
    expect(fn () => DB::table('exercises')->insert([
        'residence_id' => $residence->id,
        'mandate_id' => $mandate->id,
        'label' => '2026-HORS-BORNES',
        'started_on' => '2026-01-01', // anterieur au debut du mandat
        'ended_on' => '2026-12-31',
        'state' => 'open',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class, 'Invariant T2');

    // Note : PostgreSQL avorte la transaction courante apres l'exception du trigger.
    // Le cas passant est couvert par les tests de CreateExerciseAction.
});

test('invariant T4: postgresql trigger rejects an exercise whose mandate belongs to another residence', function () {
    $residenceA = Residence::factory()->create();
    $residenceB = Residence::factory()->create();
    $mandateB = Mandate::factory()->active()->create([
        'residence_id' => $residenceB->id,
        'started_on' => '2026-01-01',
        'ended_on' => '2027-12-31',
    ]);

    expect(fn () => DB::table('exercises')->insert([
        'residence_id' => $residenceA->id,
        'mandate_id' => $mandateB->id,
        'label' => '2026-USURPE',
        'started_on' => '2026-01-01',
        'ended_on' => '2026-12-31',
        'state' => 'open',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class, 'Invariant T4');
});

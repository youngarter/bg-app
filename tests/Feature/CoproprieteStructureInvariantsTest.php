<?php

use App\Actions\Lot\AttachLotOwnerAction;
use App\Actions\Lot\CreateLotAction;
use App\Actions\Lot\MutateLotAction;
use App\Enums\LotType;
use App\Enums\OwnershipNature;
use App\Models\Lot;
use App\Models\LotAccount;
use App\Models\LotMutation;
use App\Models\LotOwnership;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\User;
use App\Support\ResidenceContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    ResidenceContext::forget();
});

/*
|--------------------------------------------------------------------------
| Invariant S1 : Σ lots.tantiemes = residences.total_tantiemes (13 §1.1)
|--------------------------------------------------------------------------
*/

test('invariant S1: sum of lot tantiemes equals residence total_tantiemes', function () {
    $residence = Residence::factory()->create(['total_tantiemes' => 1000]);

    Lot::factory()->create(['residence_id' => $residence->id, 'tantiemes' => 350]);
    Lot::factory()->create(['residence_id' => $residence->id, 'tantiemes' => 450]);
    Lot::factory()->create(['residence_id' => $residence->id, 'tantiemes' => 200]);

    expect($residence->sumTantiemes())->toBe(1000);
    expect($residence->validateTantiemesConsistency())->toBeTrue();
});

test('invariant S1: detects tantiemes discrepancy', function () {
    $residence = Residence::factory()->create(['total_tantiemes' => 1000]);

    Lot::factory()->create(['residence_id' => $residence->id, 'tantiemes' => 350]);
    Lot::factory()->create(['residence_id' => $residence->id, 'tantiemes' => 400]); // total = 750 < 1000

    expect($residence->sumTantiemes())->toBe(750);
    expect($residence->validateTantiemesConsistency())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Invariant S2 : Σ quote_part actives = 10 000 par axe (13 §1.1 & 03 §2.2)
|--------------------------------------------------------------------------
*/

test('invariant S2: full ownership totals 10 000 basis points on principal axis', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);

    LotOwnership::factory()->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $owner->id,
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete,
        'started_on' => now()->subMonth(),
        'ended_on' => null,
    ]);

    expect($lot->validateOwnershipSum())->toBeTrue();
});

test('invariant S2: indivision totals 10 000 basis points on principal axis', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $ownerA = Owner::factory()->create(['residence_id' => $residence->id]);
    $ownerB = Owner::factory()->create(['residence_id' => $residence->id]);

    LotOwnership::factory()->indivision(6000)->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $ownerA->id,
        'started_on' => now()->subMonth(),
        'ended_on' => null,
    ]);

    LotOwnership::factory()->indivision(4000)->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $ownerB->id,
        'started_on' => now()->subMonth(),
        'ended_on' => null,
    ]);

    expect($lot->validateOwnershipSum())->toBeTrue();
});

test('invariant S2: parallel dismembered axis (usufruit and nue-propriete) totals 10 000 on each axis without cumulative 20 000 anomaly', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $usufruitier = Owner::factory()->create(['residence_id' => $residence->id]);
    $nuProprietaire = Owner::factory()->create(['residence_id' => $residence->id]);

    LotOwnership::factory()->usufruit(10000)->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $usufruitier->id,
        'started_on' => now()->subMonth(),
        'ended_on' => null,
    ]);

    LotOwnership::factory()->nuePropriete(10000)->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $nuProprietaire->id,
        'started_on' => now()->subMonth(),
        'ended_on' => null,
    ]);

    // La somme brute des quote-parts fait 20 000 bp mais l'invariant est respecté par axe (03 §2.2)
    expect($lot->validateOwnershipSum())->toBeTrue();
});

test('invariant S2: incomplete ownership share is rejected', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);

    LotOwnership::factory()->indivision(5000)->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $owner->id,
        'started_on' => now()->subMonth(),
        'ended_on' => null,
    ]);

    expect($lot->validateOwnershipSum())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Invariant S3 : Aucun chevauchement du même owner sur le même lot (13 §1.1)
|--------------------------------------------------------------------------
*/

test('invariant S3: action prevents overlapping ownership periods for the same owner on the same lot', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);

    $action = app(AttachLotOwnerAction::class);

    // 1ère période : du 01/01 au 31/12
    $action->handle($lot, $owner, [
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete,
        'started_on' => '2026-01-01',
        'ended_on' => '2026-12-31',
    ]);

    // Chevauchement : du 01/06 au 31/10 -> doit lever InvalidArgumentException
    expect(fn () => $action->handle($lot, $owner, [
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete,
        'started_on' => '2026-06-01',
        'ended_on' => '2026-10-31',
    ]))->toThrow(InvalidArgumentException::class);
});

test('invariant S3: database EXCLUDE constraint rejects overlapping ownership periods even when bypassing the action', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);

    // Insertion directe en base : 1ère période ouverte
    DB::table('lot_ownerships')->insert([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $owner->id,
        'quote_part' => 10000,
        'nature' => 'pleine_propriete',
        'started_on' => '2026-01-01',
        'ended_on' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insertion directe en base : tentative de chevauchement sur la même détention
    expect(fn () => DB::table('lot_ownerships')->insert([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $owner->id,
        'quote_part' => 10000,
        'nature' => 'pleine_propriete',
        'started_on' => '2026-05-01',
        'ended_on' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('invariant S3: consecutive non-overlapping periods for same owner on same lot are allowed', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);

    $action = app(AttachLotOwnerAction::class);

    // Période 1 : jusqu'au 14/03
    $action->handle($lot, $owner, [
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete,
        'started_on' => '2026-01-01',
        'ended_on' => '2026-03-14',
    ]);

    // Période 2 consécutive : à partir du 15/03
    $second = $action->handle($lot, $owner, [
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete,
        'started_on' => '2026-03-15',
        'ended_on' => null,
    ]);

    expect($second->exists)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Invariant S4 : Tout lot possède exactement un lot_account (13 §1.1)
|--------------------------------------------------------------------------
*/

test('invariant S4: creating a lot automatically generates its unique lot_account', function () {
    $residence = Residence::factory()->create();

    $action = app(CreateLotAction::class);
    $lot = $action->handle($residence, [
        'reference' => 'B204',
        'type' => LotType::Appartement,
        'tantiemes' => 150,
    ]);

    expect($lot->account)->not->toBeNull();
    expect($lot->account->lot_id)->toBe($lot->id);
    expect($lot->account->residence_id)->toBe($residence->id);
    expect($lot->account->code)->toBe('LOT-B204');
    expect(LotAccount::where('lot_id', $lot->id)->count())->toBe(1);
});

test('invariant S4: database unique constraint rejects duplicate lot_account on same lot', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);

    LotAccount::create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'code' => 'LOT-FIRST',
    ]);

    expect(fn () => LotAccount::create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'code' => 'LOT-SECOND',
    ]))->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| Cohérence des scopes résidences (BelongsToResidence)
|--------------------------------------------------------------------------
*/

test('residence scope consistency across lot structure models', function () {
    $residence = Residence::factory()->create();

    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $owner = Owner::factory()->create(['residence_id' => $residence->id]);

    $ownership = LotOwnership::factory()->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $owner->id,
    ]);

    $account = LotAccount::factory()->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
    ]);

    $mutation = LotMutation::factory()->create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
    ]);

    expect($ownership->residence_id)->toBe($lot->residence_id);
    expect($account->residence_id)->toBe($lot->residence_id);
    expect($mutation->residence_id)->toBe($lot->residence_id);
});

/*
|--------------------------------------------------------------------------
| Mutations (03 §5)
|--------------------------------------------------------------------------
*/

test('mutation closes outgoing ownerships, opens incoming, and records mutation event', function () {
    $residence = Residence::factory()->create();
    $lot = Lot::factory()->create(['residence_id' => $residence->id]);
    $seller = Owner::factory()->create(['residence_id' => $residence->id, 'nom' => 'Ahmed']);
    $buyer = Owner::factory()->create(['residence_id' => $residence->id, 'nom' => 'Fatima']);
    $user = User::factory()->create();

    // Vendeur détient le lot depuis le 01/01/2025
    LotOwnership::create([
        'residence_id' => $residence->id,
        'lot_id' => $lot->id,
        'owner_id' => $seller->id,
        'quote_part' => 10000,
        'nature' => OwnershipNature::PleinePropriete,
        'started_on' => '2025-01-01',
        'ended_on' => null,
    ]);

    $mutationAction = app(MutateLotAction::class);
    $mutation = $mutationAction->handle(
        $lot,
        '2026-03-15',
        [
            [
                'owner_id' => $buyer->id,
                'quote_part' => 10000,
                'nature' => OwnershipNature::PleinePropriete->value,
            ],
        ],
        prix: 150000000,
        documentPath: 'documents/mutations/acte_vente_20260315.pdf',
        actor: $user,
    );

    expect($mutation->exists)->toBeTrue();
    expect($mutation->prix)->toBe(150000000);
    expect($mutation->effective_date->toDateString())->toBe('2026-03-15');

    // L'ancienne détention est fermée la veille (14/03/2026)
    $sellerOwnership = LotOwnership::where('lot_id', $lot->id)->where('owner_id', $seller->id)->first();
    expect($sellerOwnership->ended_on->toDateString())->toBe('2026-03-14');

    // La nouvelle détention est ouverte au 15/03/2026
    $buyerOwnership = LotOwnership::where('lot_id', $lot->id)->where('owner_id', $buyer->id)->first();
    expect($buyerOwnership->started_on->toDateString())->toBe('2026-03-15');
    expect($buyerOwnership->ended_on)->toBeNull();

    // Invariant S2 respecté à toute date
    expect($lot->validateOwnershipSum(Carbon::parse('2026-03-01')))->toBeTrue(); // Ancien propriétaire
    expect($lot->validateOwnershipSum(Carbon::parse('2026-03-15')))->toBeTrue(); // Nouveau propriétaire
});

test('mutation captures balance from ledger projection at date (todo Étape 4)', function () {
    // Ce test sera connecté à la projection du grand livre lors de l'Étape 4 (03 §5)
})->skip('À brancher sur la projection du ledger à l\'Étape 4');

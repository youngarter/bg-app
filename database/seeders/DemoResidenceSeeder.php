<?php

namespace Database\Seeders;

use App\Actions\Lot\CreateLotAction;
use App\Enums\ExerciseState;
use App\Enums\LicenseStatus;
use App\Enums\LotType;
use App\Enums\MandateState;
use App\Enums\OwnershipNature;
use App\Enums\OwnerType;
use App\Models\Exercise;
use App\Models\License;
use App\Models\Lot;
use App\Models\LotOwnership;
use App\Models\Mandate;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\SyndicCompany;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoResidenceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'ys.benfatih@gmail.com')->first()
            ?? User::first()
            ?? User::factory()->create([
                'name' => 'Youness BENFATIH',
                'email' => 'ys.benfatih@gmail.com',
            ]);

        // Attribuer le statut d'administrateur plateforme au compte de démonstration
        $user->is_platform_admin = true;
        $user->save();

        // Créer la société de syndic
        $syndic = SyndicCompany::firstOrCreate(
            ['ice' => '001234567000089'],
            [
                'nom' => 'Atlas Syndic & Gestion',
                'forme_juridique' => 'SARL',
                'rc' => 'RC-12345 Casablanca',
                'adresse' => '45 Boulevard d\'Anfa, 4ème étage, Casablanca',
                'telephone' => '0522123456',
                'email' => 'contact@atlassyndic.ma',
            ]
        );

        // Rattacher l'utilisateur en tant que Gérant du syndic
        if (! $syndic->users()->where('users.id', $user->id)->exists()) {
            $syndic->users()->attach($user->id, [
                'role' => 'gerant',
            ]);
        }

        $createLotAction = app(CreateLotAction::class);

        // ==========================================
        // 1. Résidence Al Boustane (Casablanca, 10 000 tantièmes)
        // ==========================================
        $residence1 = Residence::firstOrCreate(
            ['nom' => 'Résidence Al Boustane'],
            [
                'adresse' => '12 Rue Abou Dhabi, Quartier Palmier',
                'ville' => 'Casablanca',
                'total_tantiemes' => 10000,
            ]
        );

        License::firstOrCreate(
            ['residence_id' => $residence1->id],
            [
                'status' => LicenseStatus::Active,
                'starts_on' => Carbon::now()->startOfYear(),
                'ends_on' => Carbon::now()->addYear(),
                'grace_days' => 15,
            ]
        );

        ResidenceAccess::firstOrCreate(
            ['residence_id' => $residence1->id, 'syndic_company_id' => $syndic->id],
            [
                'status' => 'active',
                'granted_at' => Carbon::now()->subMonths(6),
                'granted_by_admin_id' => $user->id,
            ]
        );

        $mandate1 = Mandate::firstOrCreate(
            ['residence_id' => $residence1->id, 'syndic_company_id' => $syndic->id],
            [
                'state' => MandateState::Active,
                // T2 : le mandat doit couvrir integralement l'exercice civil en cours.
                'started_on' => Carbon::now()->startOfYear()->toDateString(),
                'ended_on' => Carbon::now()->startOfYear()->addYears(2)->subDay()->toDateString(),
                'pv_ag_reference' => 'AG-ORD-2025-01',
            ]
        );

        Exercise::firstOrCreate(
            ['residence_id' => $residence1->id, 'label' => Carbon::now()->year.'-A'],
            [
                'mandate_id' => $mandate1->id,
                'state' => ExerciseState::Open,
                'started_on' => Carbon::now()->startOfYear()->toDateString(),
                'ended_on' => Carbon::now()->endOfYear()->toDateString(),
            ]
        );

        // Copropriétaires
        $owner1 = Owner::firstOrCreate(
            ['residence_id' => $residence1->id, 'cin' => 'BE123456'],
            [
                'type' => OwnerType::PersonnePhysique,
                'nom' => 'Bennani',
                'prenom' => 'Karim',
                'telephone' => '0661112233',
                'email' => 'k.bennani@exemple.ma',
                'adresse' => 'Apt 101, Résidence Al Boustane, Casablanca',
            ]
        );

        $owner2 = Owner::firstOrCreate(
            ['residence_id' => $residence1->id, 'cin' => 'BK987654'],
            [
                'type' => OwnerType::PersonnePhysique,
                'nom' => 'El Idrissi',
                'prenom' => 'Fatima Zahra',
                'telephone' => '0662334455',
                'email' => 'fz.idrissi@exemple.ma',
                'adresse' => 'Apt 201, Résidence Al Boustane, Casablanca',
            ]
        );

        $owner3 = Owner::firstOrCreate(
            ['residence_id' => $residence1->id, 'ice' => '009876543210000'],
            [
                'type' => OwnerType::PersonneMorale,
                'raison_sociale' => 'Société Foncière Anfa SARL',
                'telephone' => '0522998877',
                'email' => 'contact@fonciere-anfa.ma',
                'adresse' => 'Boulevard Ghandi, Casablanca',
            ]
        );

        // Lots
        if ($residence1->lots()->count() === 0) {
            $lotA1 = $createLotAction->handle($residence1, [
                'reference' => 'A-101',
                'type' => LotType::Appartement->value,
                'batiment' => 'Bâtiment A',
                'etage' => '1er étage',
                'superficie' => 110.00,
                'tantiemes' => 1250,
            ], $user);

            $lotA2 = $createLotAction->handle($residence1, [
                'reference' => 'A-201',
                'type' => LotType::Appartement->value,
                'batiment' => 'Bâtiment A',
                'etage' => '2ème étage',
                'superficie' => 135.50,
                'tantiemes' => 1500,
            ], $user);

            $lotCom = $createLotAction->handle($residence1, [
                'reference' => 'COM-01',
                'type' => LotType::Magasin->value,
                'batiment' => 'Rez-de-chaussée',
                'etage' => 'RDC',
                'superficie' => 200.00,
                'tantiemes' => 2250,
            ], $user);

            $lotPark = $createLotAction->handle($residence1, [
                'reference' => 'P-01',
                'type' => LotType::Parking->value,
                'batiment' => 'Sous-sol -1',
                'etage' => 'SS-1',
                'superficie' => 15.00,
                'tantiemes' => 150,
            ], $user);

            // Détentions
            LotOwnership::create([
                'residence_id' => $residence1->id,
                'lot_id' => $lotA1->id,
                'owner_id' => $owner1->id,
                'quote_part' => 10000,
                'nature' => OwnershipNature::PleinePropriete->value,
                'started_on' => Carbon::now()->subMonths(6)->toDateString(),
            ]);

            LotOwnership::create([
                'residence_id' => $residence1->id,
                'lot_id' => $lotA2->id,
                'owner_id' => $owner2->id,
                'quote_part' => 10000,
                'nature' => OwnershipNature::PleinePropriete->value,
                'started_on' => Carbon::now()->subMonths(6)->toDateString(),
            ]);

            LotOwnership::create([
                'residence_id' => $residence1->id,
                'lot_id' => $lotCom->id,
                'owner_id' => $owner3->id,
                'quote_part' => 10000,
                'nature' => OwnershipNature::PleinePropriete->value,
                'started_on' => Carbon::now()->subMonths(4)->toDateString(),
            ]);
        }

        // ==========================================
        // 2. Résidence Les Palmiers (Marrakech, 8 000 tantièmes - Illustre la correction tantièmes != 10 000)
        // ==========================================
        $residence2 = Residence::firstOrCreate(
            ['nom' => 'Résidence Les Palmiers'],
            [
                'adresse' => 'Avenue Mohammed VI, Hivernage',
                'ville' => 'Marrakech',
                'total_tantiemes' => 8000,
            ]
        );

        License::firstOrCreate(
            ['residence_id' => $residence2->id],
            [
                'status' => LicenseStatus::Active,
                'starts_on' => Carbon::now()->startOfYear(),
                'ends_on' => Carbon::now()->addYear(),
                'grace_days' => 15,
            ]
        );

        ResidenceAccess::firstOrCreate(
            ['residence_id' => $residence2->id, 'syndic_company_id' => $syndic->id],
            [
                'status' => 'active',
                'granted_at' => Carbon::now()->subMonths(6),
                'granted_by_admin_id' => $user->id,
            ]
        );

        $mandate2 = Mandate::firstOrCreate(
            ['residence_id' => $residence2->id, 'syndic_company_id' => $syndic->id],
            [
                'state' => MandateState::Active,
                // T2 : le mandat doit couvrir integralement l'exercice civil en cours.
                'started_on' => Carbon::now()->startOfYear()->toDateString(),
                'ended_on' => Carbon::now()->startOfYear()->addYears(2)->subDay()->toDateString(),
                'pv_ag_reference' => 'AG-CONST-2025-01',
            ]
        );

        Exercise::firstOrCreate(
            ['residence_id' => $residence2->id, 'label' => Carbon::now()->year.'-1'],
            [
                'mandate_id' => $mandate2->id,
                'state' => ExerciseState::Open,
                'started_on' => Carbon::now()->startOfYear()->toDateString(),
                'ended_on' => Carbon::now()->endOfYear()->toDateString(),
            ]
        );

        if ($residence2->lots()->count() === 0) {
            // Lot illustratif du Point 1 : 150 tantièmes sur 8 000 = 1,875 %
            $createLotAction->handle($residence2, [
                'reference' => 'V-101',
                'type' => LotType::Appartement->value,
                'batiment' => 'Villa A',
                'etage' => 'RDC',
                'superficie' => 95.00,
                'tantiemes' => 150,
            ], $user);

            $createLotAction->handle($residence2, [
                'reference' => 'V-102',
                'type' => LotType::Appartement->value,
                'batiment' => 'Villa A',
                'etage' => '1er étage',
                'superficie' => 140.00,
                'tantiemes' => 850,
            ], $user);
        }
    }
}

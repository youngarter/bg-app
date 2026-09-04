<?php

namespace Database\Factories;

use App\Models\Lot;
use App\Models\LotMutation;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LotMutation>
 */
class LotMutationFactory extends Factory
{
    protected $model = LotMutation::class;

    public function definition(): array
    {
        $residence = Residence::factory();
        $lot = Lot::factory()->for($residence);

        return [
            'residence_id' => $residence,
            'lot_id' => $lot,
            'effective_date' => now()->toDateString(),
            'outgoing_snapshot' => [
                ['owner_id' => 1, 'owner_name' => 'Ancien Propriétaire', 'quote_part' => 10000, 'nature' => 'pleine_propriete'],
            ],
            'incoming_snapshot' => [
                ['owner_id' => 2, 'owner_name' => 'Nouveau Propriétaire', 'quote_part' => 10000, 'nature' => 'pleine_propriete'],
            ],
            'balance_at_date' => 0,
            'prix' => 120000000, // 1 200 000 DH en centimes
            'document_path' => 'documents/mutations/acte_mutation.pdf',
            'created_by_user_id' => User::factory(),
        ];
    }
}

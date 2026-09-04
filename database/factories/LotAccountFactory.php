<?php

namespace Database\Factories;

use App\Models\Lot;
use App\Models\LotAccount;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LotAccount>
 */
class LotAccountFactory extends Factory
{
    protected $model = LotAccount::class;

    public function definition(): array
    {
        $residence = Residence::factory();
        $lot = Lot::factory()->for($residence);

        return [
            'residence_id' => $residence,
            'lot_id' => $lot,
            'code' => 'LOT-'.strtoupper(fake()->unique()->bothify('?##')),
        ];
    }
}

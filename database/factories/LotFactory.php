<?php

namespace Database\Factories;

use App\Enums\LotType;
use App\Models\Lot;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    protected $model = Lot::class;

    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            'reference' => strtoupper(fake()->bothify('?##')),
            'type' => LotType::Appartement,
            'batiment' => 'Bâtiment A',
            'etage' => '1er étage',
            'superficie' => 85.50,
            'tantiemes' => 100,
        ];
    }
}

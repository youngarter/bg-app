<?php

namespace Database\Factories;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Residence>
 */
class ResidenceFactory extends Factory
{
    protected $model = Residence::class;

    public function definition(): array
    {
        return [
            'nom' => 'Résidence '.fake()->streetName(),
            'adresse' => fake()->address(),
            'ville' => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Agadir']),
            'total_tantiemes' => 1000,
            'devise' => 'MAD',
            'settings' => [],
        ];
    }
}

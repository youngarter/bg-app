<?php

namespace Database\Factories;

use App\Models\SyndicCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyndicCompany>
 */
class SyndicCompanyFactory extends Factory
{
    protected $model = SyndicCompany::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->company().' Syndic',
            'forme_juridique' => fake()->randomElement(['SARL', 'SARL AU', 'SA']),
            'ice' => fake()->numerify('00############'),
            'rc' => fake()->numerify('#####'),
            'adresse' => fake()->address(),
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
        ];
    }
}

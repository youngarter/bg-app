<?php

namespace Database\Factories;

use App\Enums\OwnerType;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Owner>
 */
class OwnerFactory extends Factory
{
    protected $model = Owner::class;

    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            'user_id' => User::factory(),
            'type' => OwnerType::PersonnePhysique,
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'raison_sociale' => null,
            'cin' => strtoupper(fake()->bothify('?######')),
            'ice' => null,
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'adresse' => fake()->address(),
        ];
    }

    public function personneMorale(): static
    {
        return $this->state(fn () => [
            'type' => OwnerType::PersonneMorale,
            'nom' => null,
            'prenom' => null,
            'raison_sociale' => fake()->company(),
            'ice' => fake()->numerify('00############'),
            'cin' => null,
        ]);
    }
}

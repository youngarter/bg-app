<?php

namespace Database\Factories;

use App\Enums\SyndicRole;
use App\Models\SyndicCompany;
use App\Models\SyndicCompanyUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyndicCompanyUser>
 */
class SyndicCompanyUserFactory extends Factory
{
    protected $model = SyndicCompanyUser::class;

    public function definition(): array
    {
        return [
            'syndic_company_id' => SyndicCompany::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(SyndicRole::cases()),
        ];
    }

    public function gerant(): static
    {
        return $this->state(fn () => ['role' => SyndicRole::Gerant]);
    }

    public function gestionnaire(): static
    {
        return $this->state(fn () => ['role' => SyndicRole::Gestionnaire]);
    }

    public function comptable(): static
    {
        return $this->state(fn () => ['role' => SyndicRole::Comptable]);
    }
}

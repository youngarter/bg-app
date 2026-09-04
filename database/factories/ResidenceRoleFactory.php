<?php

namespace Database\Factories;

use App\Enums\ResidenceRoleType;
use App\Models\Residence;
use App\Models\ResidenceRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResidenceRole>
 */
class ResidenceRoleFactory extends Factory
{
    protected $model = ResidenceRole::class;

    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            'user_id' => User::factory(),
            'role' => ResidenceRoleType::PresidentConseil,
            'started_on' => now()->subMonths(6),
            'ended_on' => now()->addMonths(6),
            'granted_by_user_id' => User::factory()->platformAdmin(),
            'pv_ag_document_path' => 'documents/ag/pv_election_conseil.pdf',
        ];
    }

    public function president(): static
    {
        return $this->state(fn () => [
            'role' => ResidenceRoleType::PresidentConseil,
        ]);
    }

    public function membre(): static
    {
        return $this->state(fn () => [
            'role' => ResidenceRoleType::MembreConseil,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'started_on' => now()->subYears(2),
            'ended_on' => now()->subMonths(6),
        ]);
    }
}

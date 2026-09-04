<?php

namespace Database\Factories;

use App\Enums\DelegationState;
use App\Enums\DelegationTitle;
use App\Models\Delegation;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delegation>
 */
class DelegationFactory extends Factory
{
    protected $model = Delegation::class;

    public function definition(): array
    {
        $residence = Residence::factory();
        $user = User::factory();
        $owner = Owner::factory()->for($residence)->for($user);

        return [
            'residence_id' => $residence,
            'owner_id' => $owner,
            'user_id' => $user,
            'titre' => DelegationTitle::ViceSyndic,
            'modules' => ['appels', 'paiements', 'depenses', 'fournisseurs', 'tresorerie'],
            'started_on' => now()->subMonths(2)->toDateString(),
            'ended_on' => now()->addMonths(10)->toDateString(),
            'pv_ag_document_path' => 'documents/delegations/pv_nomination.pdf',
            'granted_by_user_id' => User::factory(),
            'state' => DelegationState::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'started_on' => now()->subYears(2)->toDateString(),
            'ended_on' => now()->subMonths(2)->toDateString(),
        ]);
    }

    public function revoquee(): static
    {
        return $this->state(fn () => [
            'state' => DelegationState::Revoquee,
        ]);
    }
}

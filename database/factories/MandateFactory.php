<?php

namespace Database\Factories;

use App\Enums\MandateState;
use App\Models\Mandate;
use App\Models\Residence;
use App\Models\SyndicCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Mandate>
 */
class MandateFactory extends Factory
{
    protected $model = Mandate::class;

    public function definition(): array
    {
        $started = Carbon::parse(fake()->dateTimeBetween('-2 years', 'now'))->startOfDay();
        // Défaut de saisie de 2 ans
        $ended = $started->copy()->addYears(2)->subDay();

        return [
            'residence_id' => Residence::factory(),
            'syndic_company_id' => SyndicCompany::factory(),
            'started_on' => $started->toDateString(),
            'ended_on' => $ended->toDateString(),
            'state' => MandateState::Active,
            'pv_ag_reference' => 'PV-AG-'.fake()->year(),
            'pv_ag_document_path' => 'documents/mandates/pv_'.fake()->uuid().'.pdf',
            'honoraires' => 5000000, // 50 000 DH en centimes
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'state' => MandateState::Draft,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'state' => MandateState::Active,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'state' => MandateState::Suspended,
        ]);
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'state' => MandateState::Terminated,
            'terminated_motif' => 'Résiliation anticipée votée en AG extraordinaire',
            'terminated_document_path' => 'documents/mandates/termination_'.fake()->uuid().'.pdf',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'state' => MandateState::Expired,
            'started_on' => now()->subYears(3)->toDateString(),
            'ended_on' => now()->subYear()->toDateString(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'state' => MandateState::Closed,
            'started_on' => now()->subYears(3)->toDateString(),
            'ended_on' => now()->subYear()->toDateString(),
        ]);
    }
}

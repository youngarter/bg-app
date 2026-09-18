<?php

namespace Database\Factories;

use App\Enums\ExerciseState;
use App\Models\Exercise;
use App\Models\Mandate;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            // T4 : le mandat doit appartenir a la MEME residence que l'exercice.
            // Une Mandate::factory() independante en creerait une seconde et le
            // trigger exercises_within_mandate rejetterait l'insertion.
            'mandate_id' => fn (array $attributes) => Mandate::factory()->create([
                'residence_id' => $attributes['residence_id'],
                // T2 : le mandat couvre largement la periode par defaut de l'exercice.
                'started_on' => '2025-01-01',
                'ended_on' => '2026-12-31',
            ])->id,
            'label' => '2025',
            'started_on' => '2025-01-01',
            'ended_on' => '2025-12-31',
            'state' => ExerciseState::Open,
            'closed_at' => null,
            'closed_by_user_id' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'state' => ExerciseState::Open,
            'closed_at' => null,
            'closed_by_user_id' => null,
        ]);
    }

    public function closing(): static
    {
        return $this->state(fn () => [
            'state' => ExerciseState::Closing,
            'closed_at' => null,
            'closed_by_user_id' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'state' => ExerciseState::Closed,
            'closed_at' => now(),
            'closed_by_user_id' => User::factory(),
        ]);
    }
}

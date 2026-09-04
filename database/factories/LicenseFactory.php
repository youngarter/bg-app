<?php

namespace Database\Factories;

use App\Enums\LicensePayer;
use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        $startsOn = now()->subMonths(3);

        return [
            'residence_id' => Residence::factory(),
            'plan' => 'standard',
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->copy()->addYear(),
            'grace_days' => 30,
            'status' => LicenseStatus::Active,
            'payer' => LicensePayer::Copropriete,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Active,
            'ends_on' => now()->addMonths(6),
        ]);
    }

    public function grace(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Grace,
            'ends_on' => now()->subDays(5),
            'grace_days' => 30,
        ]);
    }

    public function readOnly(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::ReadOnly,
            'ends_on' => now()->subDays(40),
            'grace_days' => 30,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Suspended,
        ]);
    }
}

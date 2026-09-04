<?php

namespace Database\Factories;

use App\Enums\LicenseEventType;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseEvent>
 */
class LicenseEventFactory extends Factory
{
    protected $model = LicenseEvent::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'type' => LicenseEventType::Created,
            'effective_at' => now(),
            'actor_user_id' => User::factory()->platformAdmin(),
            'note' => 'Création initiale de la licence',
        ];
    }
}

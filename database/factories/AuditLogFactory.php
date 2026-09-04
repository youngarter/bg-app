<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            'actor_user_id' => User::factory(),
            'action' => 'residence.viewed',
            'auditable_type' => Residence::class,
            'auditable_id' => null,
            'motif' => 'Consultation de routine pour contrôle comptable',
            'document_path' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'BayanTestAgent/1.0',
        ];
    }
}

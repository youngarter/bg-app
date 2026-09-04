<?php

namespace Database\Factories;

use App\Enums\ResidenceAccessStatus;
use App\Models\Residence;
use App\Models\ResidenceAccess;
use App\Models\SyndicCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResidenceAccess>
 */
class ResidenceAccessFactory extends Factory
{
    protected $model = ResidenceAccess::class;

    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            'syndic_company_id' => SyndicCompany::factory(),
            'status' => ResidenceAccessStatus::Active,
            'granted_at' => now()->subMonths(2),
            'granted_by_admin_id' => User::factory()->platformAdmin(),
            'revoked_at' => null,
            'revoked_by_admin_id' => null,
            'revoked_motif' => null,
            'revoked_document_path' => null,
            'export_generated_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ResidenceAccessStatus::Active,
            'revoked_at' => null,
            'revoked_by_admin_id' => null,
            'revoked_motif' => null,
            'revoked_document_path' => null,
            'export_generated_at' => null,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => ResidenceAccessStatus::Revoked,
            'revoked_at' => now()->subDays(3),
            'revoked_by_admin_id' => User::factory()->platformAdmin(),
            'revoked_motif' => 'Révocation suite à décision AG',
            'revoked_document_path' => 'documents/revocations/pv_ag_revocation.pdf',
            'export_generated_at' => now()->subDays(3),
        ]);
    }
}

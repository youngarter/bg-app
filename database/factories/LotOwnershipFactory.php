<?php

namespace Database\Factories;

use App\Enums\OwnershipNature;
use App\Models\Lot;
use App\Models\LotOwnership;
use App\Models\Owner;
use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LotOwnership>
 */
class LotOwnershipFactory extends Factory
{
    protected $model = LotOwnership::class;

    public function definition(): array
    {
        $residence = Residence::factory();

        return [
            'residence_id' => $residence,
            'lot_id' => Lot::factory()->for($residence),
            'owner_id' => Owner::factory()->for($residence),
            'quote_part' => 10000,
            'nature' => OwnershipNature::PleinePropriete,
            'started_on' => now()->subMonths(6)->toDateString(),
            'ended_on' => null,
            'document_path' => 'documents/ownerships/acte_achat.pdf',
        ];
    }

    public function indivision(int $quotePart = 5000): static
    {
        return $this->state(fn () => [
            'quote_part' => $quotePart,
            'nature' => OwnershipNature::Indivision,
        ]);
    }

    public function usufruit(int $quotePart = 10000): static
    {
        return $this->state(fn () => [
            'quote_part' => $quotePart,
            'nature' => OwnershipNature::Usufruit,
        ]);
    }

    public function nuePropriete(int $quotePart = 10000): static
    {
        return $this->state(fn () => [
            'quote_part' => $quotePart,
            'nature' => OwnershipNature::NuePropriete,
        ]);
    }
}

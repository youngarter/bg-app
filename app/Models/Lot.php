<?php

namespace App\Models;

use App\Enums\LotType;
use App\Enums\OwnershipNature;
use App\Models\Concerns\BelongsToResidence;
use Carbon\CarbonInterface;
use Database\Factories\LotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property string $reference
 * @property LotType $type
 * @property string|null $batiment
 * @property string|null $etage
 * @property string|null $superficie
 * @property int $tantiemes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['residence_id', 'reference', 'type', 'batiment', 'etage', 'superficie', 'tantiemes'])]
class Lot extends Model
{
    /** @use HasFactory<LotFactory> */
    use BelongsToResidence, HasFactory;

    protected function casts(): array
    {
        return [
            'type' => LotType::class,
            'tantiemes' => 'integer',
            'superficie' => 'decimal:2',
        ];
    }

    public function account(): HasOne
    {
        return $this->hasOne(LotAccount::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(LotOwnership::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class, 'lot_ownerships')
            ->using(LotOwnership::class)
            ->withPivot(['quote_part', 'nature', 'started_on', 'ended_on', 'document_path'])
            ->withTimestamps();
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(LotMutation::class);
    }

    /**
     * Get the active ownerships at a given date.
     *
     * @return Collection<int, LotOwnership>
     */
    public function activeOwnershipsAt(?CarbonInterface $date = null): Collection
    {
        return $this->ownerships()
            ->activeAt($date)
            ->get();
    }

    /**
     * Invariant S2:
     * Σ quote_part des détentions actives = 10 000 par axe (03 §2.2).
     * Axe 1 : pleine_propriete + indivision = 10 000 bp.
     * Axe 2 : usufruit = 10 000 bp et nue_propriete = 10 000 bp (sans se cumuler).
     */
    public function validateOwnershipSum(?CarbonInterface $date = null): bool
    {
        $active = $this->activeOwnershipsAt($date);

        if ($active->isEmpty()) {
            return false;
        }

        // Axe principal
        $principalOwnerships = $active->filter(fn (LotOwnership $o) => $o->nature->isPrincipalAxis());
        $principalSum = (int) $principalOwnerships->sum('quote_part');

        // Axe démembré
        $usufruitSum = (int) $active->where('nature', OwnershipNature::Usufruit)->sum('quote_part');
        $nueProprieteSum = (int) $active->where('nature', OwnershipNature::NuePropriete)->sum('quote_part');

        $hasPrincipal = $principalOwnerships->isNotEmpty();
        $hasDismembered = $usufruitSum > 0 || $nueProprieteSum > 0;

        if ($hasPrincipal && $principalSum !== 10000) {
            return false;
        }

        if ($hasDismembered) {
            if ($usufruitSum !== 10000 || $nueProprieteSum !== 10000) {
                return false;
            }
        }

        return $hasPrincipal || $hasDismembered;
    }
}

<?php

namespace App\Models;

use App\Enums\OwnershipNature;
use App\Models\Concerns\BelongsToResidence;
use Carbon\CarbonInterface;
use Database\Factories\LotOwnershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int $lot_id
 * @property int $owner_id
 * @property int $quote_part
 * @property OwnershipNature $nature
 * @property Carbon $started_on
 * @property Carbon|null $ended_on
 * @property string|null $document_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'residence_id',
    'lot_id',
    'owner_id',
    'quote_part',
    'nature',
    'started_on',
    'ended_on',
    'document_path',
])]
class LotOwnership extends Pivot
{
    /** @use HasFactory<LotOwnershipFactory> */
    use BelongsToResidence, HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lot_ownerships';

    protected function casts(): array
    {
        return [
            'quote_part' => 'integer',
            'nature' => OwnershipNature::class,
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function scopeActiveAt(Builder $query, ?CarbonInterface $date = null): Builder
    {
        $target = ($date ?? now())->toDateString();

        return $query->where('started_on', '<=', $target)
            ->where(function (Builder $sub) use ($target) {
                $sub->whereNull('ended_on')
                    ->orWhere('ended_on', '>=', $target);
            });
    }
}

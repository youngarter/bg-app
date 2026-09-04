<?php

namespace App\Models;

use App\Models\Concerns\BelongsToResidence;
use Database\Factories\LotMutationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int $lot_id
 * @property Carbon $effective_date
 * @property array<string, mixed> $outgoing_snapshot
 * @property array<string, mixed> $incoming_snapshot
 * @property int $balance_at_date
 * @property int|null $prix
 * @property string|null $document_path
 * @property int|null $created_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'residence_id',
    'lot_id',
    'effective_date',
    'outgoing_snapshot',
    'incoming_snapshot',
    'balance_at_date',
    'prix',
    'document_path',
    'created_by_user_id',
])]
class LotMutation extends Model
{
    /** @use HasFactory<LotMutationFactory> */
    use BelongsToResidence, HasFactory;

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'outgoing_snapshot' => 'array',
            'incoming_snapshot' => 'array',
            'balance_at_date' => 'integer',
            'prix' => 'integer',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

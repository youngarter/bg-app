<?php

namespace App\Models;

use App\Models\Concerns\BelongsToResidence;
use Database\Factories\LotAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int $lot_id
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['residence_id', 'lot_id', 'code'])]
class LotAccount extends Model
{
    /** @use HasFactory<LotAccountFactory> */
    use BelongsToResidence, HasFactory;

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}

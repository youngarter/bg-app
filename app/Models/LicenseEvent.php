<?php

namespace App\Models;

use App\Enums\LicenseEventType;
use App\Models\Concerns\ImmutableRecord;
use Database\Factories\LicenseEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $license_id
 * @property LicenseEventType $type
 * @property Carbon $effective_at
 * @property int|null $actor_user_id
 * @property string|null $note
 * @property Carbon $created_at
 */
#[Fillable(['license_id', 'type', 'effective_at', 'actor_user_id', 'note'])]
class LicenseEvent extends Model
{
    /** @use HasFactory<LicenseEventFactory> */
    use HasFactory, ImmutableRecord;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => LicenseEventType::class,
            'effective_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

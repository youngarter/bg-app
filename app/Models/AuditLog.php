<?php

namespace App\Models;

use App\Models\Concerns\BelongsToResidence;
use App\Models\Concerns\ImmutableRecord;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $residence_id
 * @property int|null $actor_user_id
 * @property string $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property string|null $motif
 * @property string|null $document_path
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
#[Fillable([
    'residence_id',
    'actor_user_id',
    'action',
    'auditable_type',
    'auditable_id',
    'motif',
    'document_path',
    'ip',
    'user_agent',
])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use BelongsToResidence, HasFactory, ImmutableRecord;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}

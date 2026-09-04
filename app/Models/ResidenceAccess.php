<?php

namespace App\Models;

use App\Enums\ResidenceAccessStatus;
use App\Models\Concerns\BelongsToResidence;
use Database\Factories\ResidenceAccessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int $syndic_company_id
 * @property ResidenceAccessStatus $status
 * @property Carbon $granted_at
 * @property int $granted_by_admin_id
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by_admin_id
 * @property string|null $revoked_motif
 * @property string|null $revoked_document_path
 * @property Carbon|null $export_generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'residence_id',
    'syndic_company_id',
    'status',
    'granted_at',
    'granted_by_admin_id',
    'revoked_at',
    'revoked_by_admin_id',
    'revoked_motif',
    'revoked_document_path',
    'export_generated_at',
])]
class ResidenceAccess extends Model
{
    /** @use HasFactory<ResidenceAccessFactory> */
    use BelongsToResidence, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ResidenceAccessStatus::class,
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'export_generated_at' => 'datetime',
        ];
    }

    public function syndicCompany(): BelongsTo
    {
        return $this->belongsTo(SyndicCompany::class);
    }

    public function grantedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_admin_id');
    }

    public function revokedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_admin_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ResidenceAccessStatus::Active->value);
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', ResidenceAccessStatus::Revoked->value);
    }
}

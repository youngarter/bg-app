<?php

namespace App\Models;

use App\Enums\ResidenceRoleType;
use App\Models\Concerns\BelongsToResidence;
use Carbon\CarbonInterface;
use Database\Factories\ResidenceRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int $user_id
 * @property ResidenceRoleType $role
 * @property Carbon $started_on
 * @property Carbon|null $ended_on
 * @property int|null $granted_by_user_id
 * @property string|null $pv_ag_document_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'residence_id',
    'user_id',
    'role',
    'started_on',
    'ended_on',
    'granted_by_user_id',
    'pv_ag_document_path',
])]
class ResidenceRole extends Model
{
    /** @use HasFactory<ResidenceRoleFactory> */
    use BelongsToResidence, HasFactory;

    protected function casts(): array
    {
        return [
            'role' => ResidenceRoleType::class,
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
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

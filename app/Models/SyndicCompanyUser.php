<?php

namespace App\Models;

use App\Enums\SyndicRole;
use Database\Factories\SyndicCompanyUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $syndic_company_id
 * @property int $user_id
 * @property SyndicRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['syndic_company_id', 'user_id', 'role'])]
class SyndicCompanyUser extends Pivot
{
    /** @use HasFactory<SyndicCompanyUserFactory> */
    use HasFactory;

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
    protected $table = 'syndic_company_user';

    protected function casts(): array
    {
        return [
            'role' => SyndicRole::class,
        ];
    }

    public function syndicCompany(): BelongsTo
    {
        return $this->belongsTo(SyndicCompany::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

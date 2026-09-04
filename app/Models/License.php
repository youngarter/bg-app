<?php

namespace App\Models;

use App\Enums\LicensePayer;
use App\Enums\LicenseStatus;
use App\Models\Concerns\BelongsToResidence;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property string $plan
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property int $grace_days
 * @property LicenseStatus $status
 * @property LicensePayer $payer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['residence_id', 'plan', 'starts_on', 'ends_on', 'grace_days', 'status', 'payer'])]
class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use BelongsToResidence, HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'grace_days' => 'integer',
            'status' => LicenseStatus::class,
            'payer' => LicensePayer::class,
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(LicenseEvent::class);
    }

    public function isWritable(): bool
    {
        return $this->status->allowsWrite();
    }

    public function isReadable(): bool
    {
        return $this->status->allowsRead();
    }

    public function isSuspended(): bool
    {
        return $this->status === LicenseStatus::Suspended;
    }

    public function isGrace(): bool
    {
        return $this->status === LicenseStatus::Grace;
    }

    public function isReadOnly(): bool
    {
        return $this->status === LicenseStatus::ReadOnly;
    }

    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active;
    }
}

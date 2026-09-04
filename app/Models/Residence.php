<?php

namespace App\Models;

use App\Enums\ResidenceAccessStatus;
use Database\Factories\ResidenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nom
 * @property string|null $adresse
 * @property string|null $ville
 * @property int $total_tantiemes
 * @property string $devise
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nom', 'adresse', 'ville', 'total_tantiemes', 'devise', 'settings'])]
class Residence extends Model
{
    /** @use HasFactory<ResidenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_tantiemes' => 'integer',
            'settings' => 'array',
        ];
    }

    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(ResidenceAccess::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ResidenceRole::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function owners(): HasMany
    {
        return $this->hasMany(Owner::class);
    }

    public function lotAccounts(): HasMany
    {
        return $this->hasMany(LotAccount::class);
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class);
    }

    public function currentSyndicCompany(): ?SyndicCompany
    {
        return $this->accesses()
            ->where('status', ResidenceAccessStatus::Active->value)
            ->with('syndicCompany')
            ->first()
            ?->syndicCompany;
    }

    /**
     * Invariant S1: Σ lots.tantiemes = residences.total_tantiemes (13 §1.1).
     */
    public function sumTantiemes(): int
    {
        return (int) $this->lots()->sum('tantiemes');
    }

    public function validateTantiemesConsistency(): bool
    {
        return $this->sumTantiemes() === (int) $this->total_tantiemes;
    }
}

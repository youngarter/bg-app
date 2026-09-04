<?php

namespace App\Models;

use App\Enums\OwnerType;
use App\Models\Concerns\BelongsToResidence;
use Database\Factories\OwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int|null $user_id
 * @property OwnerType $type
 * @property string|null $nom
 * @property string|null $prenom
 * @property string|null $raison_sociale
 * @property string|null $cin
 * @property string|null $ice
 * @property string|null $telephone
 * @property string|null $email
 * @property string|null $adresse
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'residence_id',
    'user_id',
    'type',
    'nom',
    'prenom',
    'raison_sociale',
    'cin',
    'ice',
    'telephone',
    'email',
    'adresse',
])]
class Owner extends Model
{
    /** @use HasFactory<OwnerFactory> */
    use BelongsToResidence, HasFactory;

    protected function casts(): array
    {
        return [
            'type' => OwnerType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(LotOwnership::class);
    }

    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class, 'lot_ownerships')
            ->using(LotOwnership::class)
            ->withPivot(['quote_part', 'nature', 'started_on', 'ended_on', 'document_path'])
            ->withTimestamps();
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class);
    }

    public function displayName(): string
    {
        if ($this->type === OwnerType::PersonneMorale) {
            return $this->raison_sociale ?? 'Personne morale';
        }

        return trim(($this->prenom ?? '').' '.($this->nom ?? '')) ?: 'Copropriétaire';
    }
}

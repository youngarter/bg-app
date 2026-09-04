<?php

namespace App\Models;

use Database\Factories\SyndicCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nom
 * @property string|null $forme_juridique
 * @property string|null $ice
 * @property string|null $rc
 * @property string|null $adresse
 * @property string|null $telephone
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nom', 'forme_juridique', 'ice', 'rc', 'adresse', 'telephone', 'email'])]
class SyndicCompany extends Model
{
    /** @use HasFactory<SyndicCompanyFactory> */
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'syndic_company_user')
            ->using(SyndicCompanyUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function syndicCompanyUsers(): HasMany
    {
        return $this->hasMany(SyndicCompanyUser::class);
    }

    public function residenceAccesses(): HasMany
    {
        return $this->hasMany(ResidenceAccess::class);
    }
}

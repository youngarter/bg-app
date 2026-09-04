<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ResidenceAccessStatus;
use App\Enums\ResidenceRoleType;
use App\Enums\ResidenceUserRole;
use App\Enums\SyndicRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_platform_admin
 * @property string $locale
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'is_platform_admin', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function syndicCompanies(): BelongsToMany
    {
        return $this->belongsToMany(SyndicCompany::class, 'syndic_company_user')
            ->using(SyndicCompanyUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function residenceRoles(): HasMany
    {
        return $this->hasMany(ResidenceRole::class);
    }

    public function owners(): HasMany
    {
        return $this->hasMany(Owner::class);
    }

    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    /**
     * Check if user is actively attached to a residence at a given date (02 §3).
     * Accréditation syndic, rôle conseil, détention de lot ou délégation active.
     */
    public function isAttachedToResidence(Residence $residence, ?CarbonInterface $at = null): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        $date = $at ?? now();

        // 1. Rôle syndic ou conseil
        if ($this->roleInResidence($residence, $date) !== null) {
            return true;
        }

        // 2. Délégation active à la date (02 §2.5)
        $hasActiveDelegation = $residence->delegations()
            ->withoutGlobalScopes()
            ->where('user_id', $this->id)
            ->activeAt($date)
            ->exists();

        if ($hasActiveDelegation) {
            return true;
        }

        // 3. Détention active de lot en tant que copropriétaire
        $hasActiveOwnership = $residence->owners()
            ->withoutGlobalScopes()
            ->where('user_id', $this->id)
            ->whereHas('ownerships', fn ($q) => $q->activeAt($date))
            ->exists();

        if ($hasActiveOwnership) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the active role of this user on a given residence at a specific date.
     */
    public function roleInResidence(Residence $residence, ?CarbonInterface $at = null): ?ResidenceUserRole
    {
        if ($this->isPlatformAdmin()) {
            return ResidenceUserRole::Admin;
        }

        $date = $at ?? now();

        // 1. Check accredited syndic company
        $hasActiveAccess = $residence->accesses()
            ->withoutGlobalScopes()
            ->where('status', ResidenceAccessStatus::Active->value)
            ->whereHas('syndicCompany.users', fn ($q) => $q->where('users.id', $this->id))
            ->with(['syndicCompany.syndicCompanyUsers' => fn ($q) => $q->where('user_id', $this->id)])
            ->first();

        if ($hasActiveAccess) {
            $pivot = $hasActiveAccess->syndicCompany
                ->syndicCompanyUsers
                ->firstWhere('user_id', $this->id);

            if ($pivot) {
                return match ($pivot->role) {
                    SyndicRole::Gerant => ResidenceUserRole::Gerant,
                    SyndicRole::Gestionnaire => ResidenceUserRole::Gestionnaire,
                    SyndicRole::Comptable => ResidenceUserRole::Comptable,
                    default => null,
                };
            }
        }

        // 2. Check active council role
        $councilRole = $residence->roles()
            ->withoutGlobalScopes()
            ->where('user_id', $this->id)
            ->activeAt($date)
            ->first();

        if ($councilRole) {
            return match ($councilRole->role) {
                ResidenceRoleType::PresidentConseil => ResidenceUserRole::PresidentConseil,
                ResidenceRoleType::MembreConseil => ResidenceUserRole::MembreConseil,
                default => null,
            };
        }

        return null;
    }
}

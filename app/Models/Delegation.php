<?php

namespace App\Models;

use App\Enums\DelegationState;
use App\Enums\DelegationTitle;
use App\Models\Concerns\BelongsToResidence;
use Carbon\CarbonInterface;
use Database\Factories\DelegationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $residence_id
 * @property int $owner_id
 * @property int $user_id
 * @property DelegationTitle $titre
 * @property array<string> $modules
 * @property Carbon $started_on
 * @property Carbon $ended_on
 * @property string|null $pv_ag_document_path
 * @property int|null $granted_by_user_id
 * @property DelegationState $state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'residence_id',
    'owner_id',
    'user_id',
    'titre',
    'modules',
    'started_on',
    'ended_on',
    'pv_ag_document_path',
    'granted_by_user_id',
    'state',
])]
class Delegation extends Model
{
    /** @use HasFactory<DelegationFactory> */
    use BelongsToResidence, HasFactory;

    /**
     * Liste des modules valides pouvant faire l'objet d'une délégation en V1 (02 §2.5).
     */
    public const VALID_MODULES = [
        'tableau_de_bord',
        'structure',
        'lots',
        'coproprietaires',
        'appels',
        'paiements',
        'depenses',
        'fournisseurs',
        'tresorerie',
        'assemblees',
        'reclamations',
        'projets',
        'carnet_entretien',
    ];

    protected function casts(): array
    {
        return [
            'titre' => DelegationTitle::class,
            'modules' => 'array',
            'started_on' => 'date',
            'ended_on' => 'date',
            'state' => DelegationState::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
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

        return $query->where('state', DelegationState::Active->value)
            ->where('started_on', '<=', $target)
            ->where('ended_on', '>=', $target);
    }

    /**
     * 02 §2.5 règle 6: modules est une liste blanche testée.
     * Un module inconnu est ignoré, jamais interprété comme « tout autoriser ».
     */
    public function allowsModule(string $module): bool
    {
        if (! in_array($module, self::VALID_MODULES, true)) {
            return false;
        }

        return in_array($module, $this->modules ?? [], true);
    }

    /**
     * 02 §2.5 règle 5: Une délégation ne donne jamais le droit d'approuver.
     */
    public function canApprove(): bool
    {
        return false;
    }
}

<?php

namespace App\Actions\Delegation;

use App\Enums\DelegationState;
use App\Enums\DelegationTitle;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Owner;
use App\Models\Residence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateDelegationAction
{
    /**
     * Crée une délégation de gestion (02 §2.5).
     *
     * @param  array{
     *     titre: DelegationTitle|string,
     *     modules: array<string>,
     *     started_on: CarbonInterface|string,
     *     ended_on: CarbonInterface|string,
     *     pv_ag_document_path?: string|null,
     * }  $attributes
     */
    public function handle(Residence $residence, Owner $owner, User $user, array $attributes, ?User $actor = null): Delegation
    {
        // 02 §2.5 règle 1: Éligibilité stricte — seul un owner de la résidence peut être délégué
        if ($owner->residence_id !== $residence->id) {
            throw new InvalidArgumentException('Éligibilité stricte violée : le copropriétaire délégué doit appartenir à la résidence.');
        }

        // Si l'owner est lié à un utilisateur différent
        if ($owner->user_id !== null && $owner->user_id !== $user->id) {
            throw new InvalidArgumentException("L'utilisateur ne correspond pas au compte utilisateur lié à ce copropriétaire.");
        }

        // 02 §2.5 règle 6: modules est une liste blanche testée
        $modules = $attributes['modules'] ?? [];
        if (empty($modules)) {
            throw new InvalidArgumentException('La délégation doit spécifier au moins un module autorisé de la liste blanche.');
        }

        $validModules = Delegation::VALID_MODULES;
        $invalidModules = array_diff($modules, $validModules);
        if (! empty($invalidModules)) {
            throw new InvalidArgumentException('Module(s) non autorisé(s) dans la liste blanche : '.implode(', ', $invalidModules));
        }

        $startedOn = $attributes['started_on'] instanceof CarbonInterface
            ? $attributes['started_on']
            : Carbon::parse($attributes['started_on']);

        $endedOn = $attributes['ended_on'] instanceof CarbonInterface
            ? $attributes['ended_on']
            : Carbon::parse($attributes['ended_on']);

        if ($endedOn->lt($startedOn)) {
            throw new InvalidArgumentException("La date d'expiration de la délégation ne peut pas être antérieure à sa date de début.");
        }

        return DB::transaction(function () use ($residence, $owner, $user, $attributes, $modules, $startedOn, $endedOn, $actor) {
            $delegation = Delegation::create([
                'residence_id' => $residence->id,
                'owner_id' => $owner->id,
                'user_id' => $user->id,
                'titre' => $attributes['titre'],
                'modules' => array_values(array_unique($modules)),
                'started_on' => $startedOn->toDateString(),
                'ended_on' => $endedOn->toDateString(),
                'pv_ag_document_path' => $attributes['pv_ag_document_path'] ?? null,
                'granted_by_user_id' => $actor?->id,
                'state' => DelegationState::Active,
            ]);

            AuditLog::create([
                'residence_id' => $residence->id,
                'actor_user_id' => $actor?->id,
                'action' => 'delegation.granted',
                'auditable_type' => Delegation::class,
                'auditable_id' => $delegation->id,
                'motif' => "Attribution de la délégation {$delegation->titre->value} à {$owner->displayName()}",
                'document_path' => $delegation->pv_ag_document_path,
            ]);

            return $delegation;
        });
    }
}

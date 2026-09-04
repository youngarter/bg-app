<?php

namespace App\Actions\Lot;

use App\Enums\OwnershipNature;
use App\Models\AuditLog;
use App\Models\Lot;
use App\Models\LotOwnership;
use App\Models\Owner;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttachLotOwnerAction
{
    /**
     * @param  array{
     *     quote_part: int,
     *     nature: OwnershipNature|string,
     *     started_on: CarbonInterface|string,
     *     ended_on?: CarbonInterface|string|null,
     *     document_path?: string|null,
     * }  $attributes
     */
    public function handle(Lot $lot, Owner $owner, array $attributes, ?User $actor = null): LotOwnership
    {
        if ($lot->residence_id !== $owner->residence_id) {
            throw new InvalidArgumentException('Le copropriétaire et le lot doivent appartenir à la même résidence.');
        }

        $startedOn = $attributes['started_on'] instanceof CarbonInterface
            ? $attributes['started_on']
            : Carbon::parse($attributes['started_on']);

        $endedOn = isset($attributes['ended_on']) && $attributes['ended_on'] !== null
            ? ($attributes['ended_on'] instanceof CarbonInterface ? $attributes['ended_on'] : Carbon::parse($attributes['ended_on']))
            : null;

        if ($endedOn && $endedOn->lt($startedOn)) {
            throw new InvalidArgumentException('La date de fin de détention ne peut pas être antérieure à la date de début.');
        }

        // Invariant S3: Vérification applicative du non-chevauchement (13 §1.1)
        // en renfort de la contrainte PostgreSQL EXCLUDE
        $overlapQuery = LotOwnership::query()
            ->withoutGlobalScopes()
            ->where('lot_id', $lot->id)
            ->where('owner_id', $owner->id)
            ->where(function ($q) use ($startedOn, $endedOn) {
                if ($endedOn === null) {
                    $q->whereNull('ended_on')
                        ->orWhere('ended_on', '>=', $startedOn->toDateString());
                } else {
                    $q->where(function ($sub) use ($startedOn, $endedOn) {
                        $sub->where('started_on', '<=', $endedOn->toDateString())
                            ->where(function ($sub2) use ($startedOn) {
                                $sub2->whereNull('ended_on')
                                    ->orWhere('ended_on', '>=', $startedOn->toDateString());
                            });
                    });
                }
            });

        if ($overlapQuery->exists()) {
            throw new InvalidArgumentException('Deux détentions du même copropriétaire sur le même lot ne peuvent pas se chevaucher (Invariant S3).');
        }

        return DB::transaction(function () use ($lot, $owner, $attributes, $startedOn, $endedOn, $actor) {
            $ownership = LotOwnership::create([
                'residence_id' => $lot->residence_id,
                'lot_id' => $lot->id,
                'owner_id' => $owner->id,
                'quote_part' => $attributes['quote_part'],
                'nature' => $attributes['nature'],
                'started_on' => $startedOn->toDateString(),
                'ended_on' => $endedOn?->toDateString(),
                'document_path' => $attributes['document_path'] ?? null,
            ]);

            AuditLog::create([
                'residence_id' => $lot->residence_id,
                'actor_user_id' => $actor?->id,
                'action' => 'lot.ownership_attached',
                'auditable_type' => LotOwnership::class,
                'auditable_id' => $ownership->id,
                'motif' => "Attribution de détention de {$ownership->quote_part} bp au copropriétaire {$owner->displayName()} sur le lot {$lot->reference}",
            ]);

            return $ownership;
        });
    }
}

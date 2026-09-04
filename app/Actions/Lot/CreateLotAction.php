<?php

namespace App\Actions\Lot;

use App\Enums\LotType;
use App\Models\AuditLog;
use App\Models\Lot;
use App\Models\LotAccount;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateLotAction
{
    /**
     * @param  array{
     *     reference: string,
     *     type: LotType|string,
     *     batiment?: string|null,
     *     etage?: string|null,
     *     superficie?: float|int|string|null,
     *     tantiemes: int,
     * }  $attributes
     */
    public function handle(Residence $residence, array $attributes, ?User $actor = null): Lot
    {
        return DB::transaction(function () use ($residence, $attributes, $actor) {
            $lot = Lot::create([
                'residence_id' => $residence->id,
                'reference' => $attributes['reference'],
                'type' => $attributes['type'],
                'batiment' => $attributes['batiment'] ?? null,
                'etage' => $attributes['etage'] ?? null,
                'superficie' => $attributes['superficie'] ?? null,
                'tantiemes' => $attributes['tantiemes'],
            ]);

            // Invariant S4: Tout lot possède exactement un lot_account (13 §1.1)
            $code = 'LOT-'.strtoupper($lot->reference);
            LotAccount::create([
                'residence_id' => $residence->id,
                'lot_id' => $lot->id,
                'code' => $code,
            ]);

            AuditLog::create([
                'residence_id' => $residence->id,
                'actor_user_id' => $actor?->id,
                'action' => 'lot.created',
                'auditable_type' => Lot::class,
                'auditable_id' => $lot->id,
                'motif' => "Création du lot {$lot->reference} ({$lot->tantiemes} tantièmes)",
            ]);

            return $lot;
        });
    }
}

<?php

namespace App\Actions\Lot;

use App\Models\AuditLog;
use App\Models\Lot;
use App\Models\LotMutation;
use App\Models\LotOwnership;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MutateLotAction
{
    /**
     * Effectue une mutation sur un lot (03 §5).
     *
     * @param  array<int, array{
     *     owner_id: int,
     *     quote_part: int,
     *     nature: string,
     *     document_path?: string|null,
     * }>  $incomingOwners
     */
    public function handle(
        Lot $lot,
        CarbonInterface|string $effectiveDate,
        array $incomingOwners,
        ?int $prix = null,
        ?string $documentPath = null,
        ?User $actor = null,
    ): LotMutation {
        if (empty($incomingOwners)) {
            throw new InvalidArgumentException('Une mutation doit comporter au moins un nouveau propriétaire entrant.');
        }

        $date = $effectiveDate instanceof CarbonInterface
            ? $effectiveDate
            : Carbon::parse($effectiveDate);

        return DB::transaction(function () use ($lot, $date, $incomingOwners, $prix, $documentPath, $actor) {
            // 1. Snapshot et clôture des détentions sortantes
            $outgoingOwnerships = $lot->ownerships()
                ->withoutGlobalScopes()
                ->activeAt($date)
                ->with('owner')
                ->get();

            $outgoingSnapshot = $outgoingOwnerships->map(fn (LotOwnership $o) => [
                'ownership_id' => $o->id,
                'owner_id' => $o->owner_id,
                'owner_name' => $o->owner?->displayName(),
                'quote_part' => $o->quote_part,
                'nature' => $o->nature->value,
                'started_on' => $o->started_on->toDateString(),
            ])->toArray();

            // Clôture des détentions sortantes la veille de la prise d'effet (03 §2.3)
            $previousDay = $date->copy()->subDay()->toDateString();
            foreach ($outgoingOwnerships as $outgoing) {
                $outgoing->ended_on = $previousDay;
                $outgoing->save();
            }

            // 2. Ouverture des détentions entrantes (started_on = effective_date)
            $incomingSnapshot = [];
            foreach ($incomingOwners as $incoming) {
                $newOwnership = LotOwnership::create([
                    'residence_id' => $lot->residence_id,
                    'lot_id' => $lot->id,
                    'owner_id' => $incoming['owner_id'],
                    'quote_part' => $incoming['quote_part'],
                    'nature' => $incoming['nature'],
                    'started_on' => $date->toDateString(),
                    'ended_on' => null,
                    'document_path' => $incoming['document_path'] ?? $documentPath,
                ]);

                $incomingSnapshot[] = [
                    'ownership_id' => $newOwnership->id,
                    'owner_id' => $newOwnership->owner_id,
                    'quote_part' => $newOwnership->quote_part,
                    'nature' => $newOwnership->nature->value,
                    'started_on' => $newOwnership->started_on->toDateString(),
                ];
            }

            // ÉTAPE 4 : brancher sur la projection du ledger (solde réel du compte de lot à date)
            $balanceAtDate = 0;

            // 3. Création de l'enregistrement de mutation
            $mutation = LotMutation::create([
                'residence_id' => $lot->residence_id,
                'lot_id' => $lot->id,
                'effective_date' => $date->toDateString(),
                'outgoing_snapshot' => $outgoingSnapshot,
                'incoming_snapshot' => $incomingSnapshot,
                'balance_at_date' => $balanceAtDate,
                'prix' => $prix,
                'document_path' => $documentPath,
                'created_by_user_id' => $actor?->id,
            ]);

            AuditLog::create([
                'residence_id' => $lot->residence_id,
                'actor_user_id' => $actor?->id,
                'action' => 'lot.mutated',
                'auditable_type' => LotMutation::class,
                'auditable_id' => $mutation->id,
                'motif' => "Mutation du lot {$lot->reference} à la date du {$date->toDateString()}",
                'document_path' => $documentPath,
            ]);

            return $mutation;
        });
    }
}

<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Models\AuditLog;
use App\Models\Mandate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ExpireMandatesAction
{
    /**
     * Transition system active -> expired when ends_on is reached.
     */
    public function handle(?CarbonInterface $asOfDate = null): int
    {
        $today = $asOfDate ? $asOfDate->copy()->startOfDay() : Carbon::now()->startOfDay();

        $mandates = Mandate::query()
            ->withoutGlobalScopes()
            ->where('state', MandateState::Active->value)
            ->where('ended_on', '<', $today->toDateString())
            ->get();

        $count = 0;
        foreach ($mandates as $mandate) {
            $mandate->state = MandateState::Expired;
            $mandate->save();

            AuditLog::create([
                'residence_id' => $mandate->residence_id,
                'actor_user_id' => null, // Système
                'action' => 'mandate.expired',
                'auditable_type' => Mandate::class,
                'auditable_id' => $mandate->id,
                'motif' => 'Expiration automatique du mandat à sa date d\'échéance',
            ]);

            $count++;
        }

        return $count;
    }
}

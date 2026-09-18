<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Models\AuditLog;
use App\Models\Mandate;
use App\Models\Residence;
use App\Models\SyndicCompany;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CreateMandateAction
{
    /**
     * Create a mandate.
     * Note: Duration is not hardcoded to 2 years; free duration is supported.
     */
    public function handle(
        Residence $residence,
        SyndicCompany $syndicCompany,
        CarbonInterface|string $startedOn,
        CarbonInterface|string $endedOn,
        ?string $pvAgReference = null,
        ?string $pvAgDocumentPath = null,
        ?int $honoraires = null,
        bool $activate = false,
        ?User $actor = null
    ): Mandate {
        $started = $startedOn instanceof CarbonInterface ? $startedOn : Carbon::parse($startedOn);
        $ended = $endedOn instanceof CarbonInterface ? $endedOn : Carbon::parse($endedOn);

        if ($started->gt($ended)) {
            throw new InvalidArgumentException('La date de début du mandat doit être antérieure ou égale à la date de fin.');
        }

        if ($activate) {
            $overlappingActive = Mandate::query()
                ->where('residence_id', $residence->id)
                ->whereIn('state', [MandateState::Active->value, MandateState::Suspended->value])
                ->where(function ($query) use ($started, $ended) {
                    $query->where('started_on', '<=', $ended->toDateString())
                        ->where('ended_on', '>=', $started->toDateString());
                })
                ->exists();

            if ($overlappingActive) {
                throw new InvalidArgumentException('Un mandat actif ou suspendu chevauchant cette période existe déjà pour cette résidence.');
            }
        }

        $mandate = Mandate::create([
            'residence_id' => $residence->id,
            'syndic_company_id' => $syndicCompany->id,
            'started_on' => $started->toDateString(),
            'ended_on' => $ended->toDateString(),
            'state' => $activate ? MandateState::Active : MandateState::Draft,
            'pv_ag_reference' => $pvAgReference,
            'pv_ag_document_path' => $pvAgDocumentPath,
            'honoraires' => $honoraires,
        ]);

        if ($actor) {
            AuditLog::create([
                'residence_id' => $residence->id,
                'actor_user_id' => $actor->id,
                'action' => $activate ? 'mandate.created_active' : 'mandate.created_draft',
                'auditable_type' => Mandate::class,
                'auditable_id' => $mandate->id,
            ]);
        }

        return $mandate;
    }
}

<?php

namespace App\Actions\Mandate;

use App\Enums\MandateState;
use App\Exceptions\InvalidStateException;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\Mandate;
use App\Models\User;
use InvalidArgumentException;

class TerminateMandateAction
{
    /**
     * Transition active -> terminated (Irreversible).
     */
    public function handle(Mandate $mandate, string $motif, string $documentPath, ?User $actor = null): Mandate
    {
        if (! in_array($mandate->state, [MandateState::Active, MandateState::Suspended], true)) {
            throw new InvalidStateException('Seul un mandat actif ou suspendu peut être résilié.');
        }

        if (mb_strlen(trim($motif)) < 10) {
            throw new InvalidTransitionMotifException('Le motif de résiliation doit contenir au moins 10 caractères.');
        }

        if (empty(trim($documentPath))) {
            throw new InvalidArgumentException('La pièce justificative de résiliation est obligatoire.');
        }

        $mandate->state = MandateState::Terminated;
        $mandate->terminated_motif = $motif;
        $mandate->terminated_document_path = $documentPath;
        $mandate->save();

        // 11 §12.1 : Toute transition irréversible écrit obligatoirement un audit_logs
        AuditLog::create([
            'residence_id' => $mandate->residence_id,
            'actor_user_id' => $actor?->id,
            'action' => 'mandate.terminated',
            'auditable_type' => Mandate::class,
            'auditable_id' => $mandate->id,
            'motif' => $motif,
        ]);

        return $mandate;
    }
}

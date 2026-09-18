<?php

namespace App\Actions\Exercise;

use App\Enums\ExerciseState;
use App\Exceptions\InvalidStateException;
use App\Models\AuditLog;
use App\Models\Exercise;
use App\Models\User;

class FinalizeExerciseClosingAction
{
    /**
     * Transition closing -> closed (Irreversible).
     *
     * Note d'architecture V1 (04 §4) :
     * La clôture définitive V1 gèle et reporte. Les contrôles bloquants complets
     * sont connectés au fil des étapes ultérieures :
     *
     * // ÉTAPE 4 : Contrôle automatique — solde de chaque compte de lot = projection du ledger
     * // ÉTAPE 4 : Contrôle automatique — somme des lignes du ledger équilibrée sur chaque pièce
     * // ÉTAPE 4 : Écritures de clôture — solder les comptes de produits (711x) vers report à nouveau (119x)
     * // ÉTAPE 4 : Production et archivage de la balance et du grand livre figés
     * // ÉTAPE 4 : À-nouveaux — réouverture des comptes de bilan (342x, 512/516, 119x) sur le nouvel exercice
     * // ÉTAPE 5 : Contrôle bloquant — approbation du conseil syndical (approval_requests)
     * // ÉTAPE 6 : Contrôle automatique — aucun appel en état draft ou submitted
     * // ÉTAPE 7 : Contrôle automatique — aucun paiement non confirmé
     * // ÉTAPE 7 : Contrôle automatique — aucune imputation en attente
     */
    public function handle(Exercise $exercise, User $closedBy): Exercise
    {
        if ($exercise->state !== ExerciseState::Closing) {
            throw new InvalidStateException('Seul un exercice en cours de clôture peut être définitivement clôturé.');
        }

        $exercise->state = ExerciseState::Closed;
        $exercise->closed_at = now();
        $exercise->closed_by_user_id = $closedBy->id;
        $exercise->save();

        // 11 §12.1 : Toute transition irréversible écrit obligatoirement un audit_logs
        AuditLog::create([
            'residence_id' => $exercise->residence_id,
            'actor_user_id' => $closedBy->id,
            'action' => 'exercise.closed',
            'auditable_type' => Exercise::class,
            'auditable_id' => $exercise->id,
            'motif' => 'Clôture définitive de l\'exercice comptable '.$exercise->label,
        ]);

        return $exercise;
    }
}

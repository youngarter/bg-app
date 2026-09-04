<?php

namespace App\Actions\ResidenceAccess;

use App\Enums\ResidenceAccessStatus;
use App\Exceptions\InvalidTransitionMotifException;
use App\Models\AuditLog;
use App\Models\ResidenceAccess;
use App\Models\User;
use App\Services\ExitExportService;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RevokeResidenceAccessAction
{
    public function __construct(
        protected ExitExportService $exitExportService,
    ) {}

    /**
     * Revoke an accredited syndic company access on a residence.
     * Irreversible. Generates blocking exit export.
     */
    public function handle(
        ResidenceAccess $access,
        User $admin,
        string $motif,
        string $documentPath,
        CarbonInterface|string $effectiveDate,
    ): ResidenceAccess {
        if (! $admin->isPlatformAdmin()) {
            throw new AuthorizationException('Seul un administrateur plateforme peut révoquer un accès syndic.');
        }

        if ($access->status === ResidenceAccessStatus::Revoked) {
            throw new InvalidArgumentException('Cet accès est déjà révoqué. Une accréditation révoquée ne peut pas être révoquée à nouveau.');
        }

        $trimmedMotif = trim($motif);
        if (mb_strlen($trimmedMotif) < 10) {
            throw new InvalidTransitionMotifException('Le motif de révocation est obligatoire et doit contenir au moins 10 caractères.');
        }

        $trimmedDoc = trim($documentPath);
        if (empty($trimmedDoc)) {
            throw new InvalidArgumentException('La pièce justificative de révocation est obligatoire.');
        }

        return DB::transaction(function () use ($access, $admin, $trimmedMotif, $trimmedDoc, $effectiveDate) {
            // 01 §5 & User Correction 4: L'export de sortie est obligatoire et bloquant.
            // S'il échoue, une exception est levée et la transaction est annulée.
            $this->exitExportService->generate($access);

            $now = now();

            $access->status = ResidenceAccessStatus::Revoked;
            $access->revoked_at = $effectiveDate instanceof CarbonInterface ? $effectiveDate : Carbon::parse($effectiveDate);
            $access->revoked_by_admin_id = $admin->id;
            $access->revoked_motif = $trimmedMotif;
            $access->revoked_document_path = $trimmedDoc;
            $access->export_generated_at = $now;
            $access->save();

            AuditLog::create([
                'residence_id' => $access->residence_id,
                'actor_user_id' => $admin->id,
                'action' => 'access.revoked',
                'auditable_type' => ResidenceAccess::class,
                'auditable_id' => $access->id,
                'motif' => $trimmedMotif,
                'document_path' => $trimmedDoc,
            ]);

            return $access;
        });
    }
}

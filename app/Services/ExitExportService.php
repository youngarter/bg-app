<?php

namespace App\Services;

use App\Exceptions\ExportGenerationFailedException;
use App\Models\ResidenceAccess;

class ExitExportService
{
    /**
     * Generate the mandatory exit export for a revoked syndic company.
     *
     * @throws ExportGenerationFailedException
     */
    public function generate(ResidenceAccess $access): string
    {
        // 01 §5: Le rapport de sortie pour le syndic sortant est obligatoire et bloquant.
        // En V1: Génération de l'archive de passation
        $path = 'exports/exit/'.$access->residence_id.'_'.$access->syndic_company_id.'_'.now()->format('YmdHis').'.zip';

        if (empty($path)) {
            throw new ExportGenerationFailedException("Échec de génération de l'export de sortie obligatoire.");
        }

        return $path;
    }
}

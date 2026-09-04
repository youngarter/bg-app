<?php

namespace App\Http\Middleware;

use App\Enums\LicenseStatus;
use App\Models\Residence;
use App\Models\User;
use App\Support\ResidenceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckResidenceLicense
{
    /**
     * Handle an incoming request.
     * Verrou 1: La licence de la résidence autorise l'opération ?
     * active/grace -> R+W | read_only -> R | suspended -> 403
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        // L'administrateur plateforme contourne le verrou 1 (02 §3)
        if ($user?->isPlatformAdmin()) {
            return $next($request);
        }

        $residence = $this->resolveResidence($request);

        if (! $residence) {
            return $next($request);
        }

        ResidenceContext::set($residence);

        $license = $residence->license;

        if (! $license || $license->status === LicenseStatus::Suspended) {
            abort(403, 'Accès interdit : la licence de cette résidence est suspendue ou introuvable.');
        }

        if ($license->status === LicenseStatus::ReadOnly && ! $request->isMethodSafe()) {
            abort(403, 'Opération d\'écriture interdite : la licence de cette résidence est en lecture seule.');
        }

        return $next($request);
    }

    protected function resolveResidence(Request $request): ?Residence
    {
        $routeParam = $request->route('residence');

        if ($routeParam instanceof Residence) {
            return $routeParam;
        }

        if (is_numeric($routeParam)) {
            return Residence::find((int) $routeParam);
        }

        $id = $request->header('X-Residence-Id') ?? $request->input('residence_id');

        return $id ? Residence::find((int) $id) : null;
    }
}

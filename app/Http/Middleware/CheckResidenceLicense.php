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

        // Si le paramètre {residence} est présent dans l'URL, c'est lui qui fait foi exclusivement
        if ($request->route() && $request->route()->hasParameter('residence')) {
            $routeParam = $request->route('residence');
            $residence = null;

            if ($routeParam instanceof Residence) {
                $residence = $routeParam;
            } elseif (is_numeric($routeParam) || is_string($routeParam)) {
                $residence = Residence::query()->withoutGlobalScopes()->find($routeParam);
            }

            if (! $residence) {
                abort(404, 'Résidence introuvable.');
            }
        } else {
            $residence = $this->resolveFallbackResidence($request);
        }

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

    protected function resolveFallbackResidence(Request $request): ?Residence
    {
        $id = $request->header('X-Residence-Id') ?? $request->input('residence_id');
        if ($id) {
            return is_numeric($id) ? Residence::query()->withoutGlobalScopes()->find((int) $id) : null;
        }

        return ResidenceContext::get();
    }
}

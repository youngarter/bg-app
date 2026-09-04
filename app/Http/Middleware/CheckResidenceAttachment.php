<?php

namespace App\Http\Middleware;

use App\Models\Residence;
use App\Models\User;
use App\Support\ResidenceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckResidenceAttachment
{
    /**
     * Handle an incoming request.
     * Verrou 2: Le lien de l'utilisateur à cette résidence est-il ACTIF À CETTE DATE ?
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentification requise.');
        }

        // L'administrateur plateforme contourne le verrou 2 (02 §3)
        if ($user->isPlatformAdmin()) {
            return $next($request);
        }

        $residence = $this->resolveResidence($request);

        if (! $residence) {
            return $next($request);
        }

        ResidenceContext::set($residence);

        // Le verrou 2 teste toujours une plage de dates, jamais un booléen (02 §3)
        // Rôle syndic, conseil, détention de lot ou délégation active
        if (! $user->isAttachedToResidence($residence, now())) {
            abort(403, 'Accès interdit : aucun rattachement actif à cette résidence à cette date.');
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

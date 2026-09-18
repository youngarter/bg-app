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

            ResidenceContext::set($residence);

            if (! $user->isAttachedToResidence($residence, now())) {
                abort(403, 'Accès interdit : aucun rattachement actif à cette résidence à cette date.');
            }

            return $next($request);
        }

        // Sinon (route sans {residence}), repli sur header, input ou ResidenceContext
        $residence = $this->resolveFallbackResidence($request);

        if (! $residence) {
            return $next($request);
        }

        ResidenceContext::set($residence);

        // Le verrou 2 teste toujours une plage de dates, jamais un booléen (02 §3)
        if (! $user->isAttachedToResidence($residence, now())) {
            abort(403, 'Accès interdit : aucun rattachement actif à cette résidence à cette date.');
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

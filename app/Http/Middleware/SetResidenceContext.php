<?php

namespace App\Http\Middleware;

use App\Models\Residence;
use App\Models\User;
use App\Support\ResidenceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetResidenceContext
{
    /**
     * Handle an incoming request.
     * Sets the active residence in session and ResidenceContext for authenticated requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $sessionResidenceId = session('current_residence_id');
        $activeResidence = null;

        if ($sessionResidenceId) {
            $candidate = Residence::query()->withoutGlobalScopes()->find($sessionResidenceId);
            if ($candidate && $user->isAttachedToResidence($candidate)) {
                $activeResidence = $candidate;
            } else {
                session()->forget('current_residence_id');
            }
        }

        if (! $activeResidence) {
            $accessible = $user->accessibleResidences();

            if ($accessible->isNotEmpty()) {
                // Tri déterministe par nom (déjà assuré par accessibleResidences)
                $activeResidence = $accessible->first();
                session(['current_residence_id' => $activeResidence->id]);
            }
        }

        if ($activeResidence) {
            ResidenceContext::set($activeResidence);
        } else {
            ResidenceContext::forget();
        }

        return $next($request);
    }
}

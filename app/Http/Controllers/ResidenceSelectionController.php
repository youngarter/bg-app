<?php

namespace App\Http\Controllers;

use App\Models\Residence;
use App\Support\ResidenceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResidenceSelectionController extends Controller
{
    /**
     * Switch the active residence for the authenticated user.
     */
    public function select(Request $request, Residence $residence): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAttachedToResidence($residence)) {
            abort(403, 'Accès interdit : vous n\'avez pas accès à cette résidence.');
        }

        session(['current_residence_id' => $residence->id]);
        ResidenceContext::set($residence);

        return redirect()->back(fallback: route('dashboard'));
    }
}

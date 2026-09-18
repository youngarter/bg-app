<?php

namespace App\Http\Controllers;

use App\Support\ResidenceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicenseSuspendedController extends Controller
{
    /**
     * Display the suspended license dedicated page.
     */
    public function __invoke(Request $request): Response
    {
        $residence = ResidenceContext::get();

        return Inertia::render('license/suspended', [
            'residence' => $residence ? [
                'id' => $residence->id,
                'nom' => $residence->nom,
            ] : null,
        ]);
    }
}

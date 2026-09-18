<?php

namespace App\Http\Middleware;

use App\Enums\LicenseStatus;
use App\Support\ResidenceContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $residence = ResidenceContext::get();
        $license = $residence ? $residence->license()->withoutGlobalScopes()->first() : null;

        $canWrite = true;
        $licenseData = null;

        if ($license) {
            $isGrace = $license->status === LicenseStatus::Grace;
            $isReadOnly = $license->status === LicenseStatus::ReadOnly;
            $isSuspended = $license->status === LicenseStatus::Suspended;
            $canWrite = in_array($license->status, [LicenseStatus::Active, LicenseStatus::Grace], true);

            $licenseData = [
                'status' => $license->status->value,
                'status_label' => $license->status->label(),
                'ends_on' => $license->ends_on?->toDateString(),
                'grace_days' => $license->grace_days,
                'grace_ends_on' => $license->ends_on ? $license->ends_on->copy()->addDays($license->grace_days)->toDateString() : null,
                'can_write' => $canWrite,
                'is_grace' => $isGrace,
                'is_read_only' => $isReadOnly,
                'is_suspended' => $isSuspended,
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'residence' => $residence ? [
                'id' => $residence->id,
                'nom' => $residence->nom,
                'adresse' => $residence->adresse,
                'ville' => $residence->ville,
                'code_postal' => $residence->code_postal,
                'total_tantiemes' => $residence->total_tantiemes,
            ] : null,
            'accessibleResidences' => fn () => $user
                ? $user->accessibleResidences()->map(fn ($r) => [
                    'id' => $r->id,
                    'nom' => $r->nom,
                    'ville' => $r->ville,
                ])
                : [],
            'currentRole' => $residence && $user ? $user->roleInResidence($residence)?->value : null,
            'license' => $licenseData,
            'canWrite' => $canWrite,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

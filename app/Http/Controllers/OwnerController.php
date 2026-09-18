<?php

namespace App\Http\Controllers;

use App\Enums\OwnerType;
use App\Models\AuditLog;
use App\Models\Owner;
use App\Support\ResidenceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OwnerController extends Controller
{
    /**
     * Display a listing of owners.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Owner::class);

        $residence = ResidenceContext::get();
        if (! $residence) {
            abort(403, 'Aucune résidence sélectionnée.');
        }

        $query = Owner::query()
            ->where('residence_id', $residence->id)
            ->with(['ownerships' => function ($q) {
                $q->whereNull('ended_on')->with('lot');
            }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                    ->orWhere('prenom', 'ilike', "%{$search}%")
                    ->orWhere('raison_sociale', 'ilike', "%{$search}%")
                    ->orWhere('cin', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $owners = $query->orderBy('nom', 'asc')
            ->orderBy('raison_sociale', 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('owners/index', [
            'owners' => $owners,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
            ],
            'ownerTypes' => array_map(fn (OwnerType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], OwnerType::cases()),
        ]);
    }

    /**
     * Store a newly created owner.
     * Note: user_id is never mandatory; an owner can exist without a user account.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Owner::class);

        $residence = ResidenceContext::get();
        if (! $residence) {
            abort(403, 'Aucune résidence sélectionnée.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::enum(OwnerType::class)],
            'nom' => ['nullable', 'string', 'max:100'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'raison_sociale' => ['nullable', 'string', 'max:150'],
            'cin' => ['nullable', 'string', 'max:30'],
            'ice' => ['nullable', 'string', 'max:30'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['type'] === OwnerType::PersonnePhysique->value && empty($validated['nom'])) {
            return redirect()->back()->withErrors(['nom' => 'Le nom est obligatoire pour une personne physique.'])->withInput();
        }

        if ($validated['type'] === OwnerType::PersonneMorale->value && empty($validated['raison_sociale'])) {
            return redirect()->back()->withErrors(['raison_sociale' => 'La raison sociale ou désignation est obligatoire.'])->withInput();
        }

        $owner = Owner::create([
            'residence_id' => $residence->id,
            'user_id' => null, // Compte de connexion non obligatoire
            ...$validated,
        ]);

        AuditLog::create([
            'residence_id' => $residence->id,
            'actor_user_id' => $request->user()?->id,
            'action' => 'owner.created',
            'auditable_type' => Owner::class,
            'auditable_id' => $owner->id,
            'motif' => 'Enregistrement du copropriétaire '.($owner->raison_sociale ?: "{$owner->prenom} {$owner->nom}"),
        ]);

        return redirect()->route('owners.show', $owner->id)
            ->with('success', 'Copropriétaire enregistré avec succès.');
    }

    /**
     * Display the specified owner.
     */
    public function show(Owner $owner): Response
    {
        Gate::authorize('view', $owner);

        $owner->load([
            'user',
            'ownerships' => fn ($q) => $q->orderBy('started_on', 'desc')->with('lot'),
        ]);

        return Inertia::render('owners/show', [
            'owner' => [
                'id' => $owner->id,
                'type' => $owner->type->value,
                'type_label' => $owner->type->label(),
                'nom' => $owner->nom,
                'prenom' => $owner->prenom,
                'raison_sociale' => $owner->raison_sociale,
                'display_name' => $owner->raison_sociale ?: "{$owner->prenom} {$owner->nom}",
                'cin' => $owner->cin,
                'ice' => $owner->ice,
                'telephone' => $owner->telephone,
                'email' => $owner->email,
                'adresse' => $owner->adresse,
                'has_user_account' => $owner->user_id !== null,
                'ownerships' => $owner->ownerships->map(fn ($o) => [
                    'id' => $o->id,
                    'lot_id' => $o->lot_id,
                    'lot_reference' => $o->lot ? $o->lot->reference : 'Inconnu',
                    'lot_type' => $o->lot ? $o->lot->type->label() : '',
                    'lot_tantiemes' => $o->lot ? $o->lot->tantiemes : 0,
                    'nature' => $o->nature->value,
                    'nature_label' => $o->nature->label(),
                    'quote_part' => $o->quote_part,
                    'started_on' => $o->started_on->toDateString(),
                    'ended_on' => $o->ended_on?->toDateString(),
                    'is_active' => $o->ended_on === null,
                ]),
            ],
            'ownerTypes' => array_map(fn (OwnerType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], OwnerType::cases()),
        ]);
    }

    /**
     * Update the specified owner.
     */
    public function update(Request $request, Owner $owner): RedirectResponse
    {
        Gate::authorize('update', $owner);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(OwnerType::class)],
            'nom' => ['nullable', 'string', 'max:100'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'raison_sociale' => ['nullable', 'string', 'max:150'],
            'cin' => ['nullable', 'string', 'max:30'],
            'ice' => ['nullable', 'string', 'max:30'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ]);

        $owner->update($validated);

        return redirect()->back()->with('success', 'Copropriétaire mis à jour avec succès.');
    }
}

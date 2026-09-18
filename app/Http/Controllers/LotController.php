<?php

namespace App\Http\Controllers;

use App\Actions\Lot\CreateLotAction;
use App\Enums\LotType;
use App\Enums\OwnershipNature;
use App\Models\Lot;
use App\Support\ResidenceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LotController extends Controller
{
    /**
     * Display a paginated listing of lots.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Lot::class);

        $residence = ResidenceContext::get();
        if (! $residence) {
            abort(403, 'Aucune résidence sélectionnée.');
        }

        $query = Lot::query()
            ->where('residence_id', $residence->id)
            ->with(['account', 'ownerships' => function ($q) {
                $q->whereNull('ended_on')->with('owner');
            }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ilike', "%{$search}%")
                    ->orWhere('batiment', 'ilike', "%{$search}%")
                    ->orWhere('etage', 'ilike', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sortField = in_array($request->input('sort'), ['reference', 'type', 'batiment', 'etage', 'tantiemes'], true)
            ? $request->input('sort')
            : 'reference';
        $sortDirection = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $lots = $query->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('lots/index', [
            'lots' => $lots,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
            'lotTypes' => array_map(fn (LotType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], LotType::cases()),
            'residenceTotalTantiemes' => (int) $residence->total_tantiemes,
        ]);
    }

    /**
     * Store a newly created lot in storage.
     */
    public function store(Request $request, CreateLotAction $createLotAction): RedirectResponse
    {
        Gate::authorize('create', Lot::class);

        $residence = ResidenceContext::get();
        if (! $residence) {
            abort(403, 'Aucune résidence sélectionnée.');
        }

        $validated = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:50',
                Rule::unique('lots', 'reference')->where('residence_id', $residence->id),
            ],
            'type' => ['required', Rule::enum(LotType::class)],
            'batiment' => ['nullable', 'string', 'max:100'],
            'etage' => ['nullable', 'string', 'max:50'],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'tantiemes' => ['required', 'integer', 'min:1'],
        ], [
            'reference.required' => 'Le numéro ou la référence du lot est obligatoire.',
            'reference.unique' => 'Ce numéro de lot existe déjà dans cette copropriété.',
            'type.required' => 'Le type de lot est obligatoire.',
            'tantiemes.required' => 'Les tantièmes du lot sont obligatoires.',
            'tantiemes.min' => 'Les tantièmes doivent être au minimum de 1.',
        ]);

        $lot = $createLotAction->handle($residence, $validated, $request->user());

        return redirect()->route('lots.show', $lot->id)
            ->with('success', "Le lot {$lot->reference} a été créé avec succès.");
    }

    /**
     * Display the specified lot with its dated ownership timeline.
     */
    public function show(Lot $lot): Response
    {
        Gate::authorize('view', $lot);

        $residence = ResidenceContext::get() ?? $lot->residence;

        $lot->load([
            'account',
            'ownerships' => fn ($q) => $q->orderBy('started_on', 'desc')->with('owner'),
        ]);

        $availableOwners = $residence->owners()
            ->orderBy('nom')
            ->get(['id', 'type', 'nom', 'prenom', 'raison_sociale']);

        return Inertia::render('lots/show', [
            'lot' => [
                'id' => $lot->id,
                'reference' => $lot->reference,
                'type' => $lot->type->value,
                'type_label' => $lot->type->label(),
                'batiment' => $lot->batiment,
                'etage' => $lot->etage,
                'superficie' => $lot->superficie,
                'tantiemes' => $lot->tantiemes,
                'account' => $lot->account ? [
                    'id' => $lot->account->id,
                    'code' => $lot->account->code,
                ] : null,
                'ownerships' => $lot->ownerships->map(fn ($o) => [
                    'id' => $o->id,
                    'owner_id' => $o->owner_id,
                    'owner_name' => $o->owner ? ($o->owner->raison_sociale ?: "{$o->owner->prenom} {$o->owner->nom}") : 'Inconnu',
                    'owner_type' => $o->owner?->type?->value,
                    'nature' => $o->nature->value,
                    'nature_label' => $o->nature->label(),
                    'quote_part' => $o->quote_part,
                    'started_on' => $o->started_on->toDateString(),
                    'ended_on' => $o->ended_on?->toDateString(),
                    'is_active' => $o->ended_on === null,
                    'document_path' => $o->document_path,
                ]),
            ],
            'availableOwners' => $availableOwners->map(fn ($owner) => [
                'id' => $owner->id,
                'label' => $owner->raison_sociale ?: "{$owner->prenom} {$owner->nom}",
                'type' => $owner->type->value,
            ]),
            'ownershipNatures' => array_map(fn (OwnershipNature $n) => [
                'value' => $n->value,
                'label' => $n->label(),
            ], OwnershipNature::cases()),
            'lotTypes' => array_map(fn (LotType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], LotType::cases()),
            'residenceTotalTantiemes' => (int) $residence->total_tantiemes,
        ]);
    }

    /**
     * Update the specified lot in storage.
     */
    public function update(Request $request, Lot $lot): RedirectResponse
    {
        Gate::authorize('update', $lot);

        $validated = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:50',
                Rule::unique('lots', 'reference')
                    ->where('residence_id', $lot->residence_id)
                    ->ignore($lot->id),
            ],
            'type' => ['required', Rule::enum(LotType::class)],
            'batiment' => ['nullable', 'string', 'max:100'],
            'etage' => ['nullable', 'string', 'max:50'],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'tantiemes' => ['required', 'integer', 'min:1'],
        ], [
            'reference.required' => 'Le numéro ou la référence du lot est obligatoire.',
            'reference.unique' => 'Ce numéro de lot existe déjà dans cette copropriété.',
            'type.required' => 'Le type de lot est obligatoire.',
            'tantiemes.required' => 'Les tantièmes du lot sont obligatoires.',
            'tantiemes.min' => 'Les tantièmes doivent être au minimum de 1.',
        ]);

        $lot->update($validated);

        return redirect()->back()->with('success', 'Le lot a été mis à jour avec succès.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Lot\AttachLotOwnerAction;
use App\Actions\Lot\MutateLotAction;
use App\Enums\OwnershipNature;
use App\Models\Lot;
use App\Models\Owner;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LotOwnershipController extends Controller
{
    /**
     * Attach an owner to a lot with a dated ownership.
     */
    public function store(Request $request, Lot $lot, AttachLotOwnerAction $action): RedirectResponse
    {
        Gate::authorize('update', $lot);

        $validated = $request->validate([
            'owner_id' => [
                'required',
                Rule::exists('owners', 'id')->where('residence_id', $lot->residence_id),
            ],
            'quote_part' => ['required', 'integer', 'min:1', 'max:10000'],
            'nature' => ['required', Rule::enum(OwnershipNature::class)],
            'started_on' => ['required', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'document_path' => ['nullable', 'string', 'max:255'],
        ], [
            'owner_id.required' => 'Le copropriétaire est obligatoire.',
            'owner_id.exists' => 'Ce copropriétaire n\'appartient pas à cette résidence.',
            'quote_part.required' => 'La quote-part en points de base est obligatoire.',
            'quote_part.max' => 'La quote-part ne peut pas excéder 10 000 points de base (100 %).',
            'started_on.required' => 'La date de début de détention est obligatoire.',
            'ended_on.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        $owner = Owner::findOrFail($validated['owner_id']);

        try {
            $action->handle(
                $lot,
                $owner,
                [
                    'quote_part' => (int) $validated['quote_part'],
                    'nature' => $validated['nature'],
                    'started_on' => $validated['started_on'],
                    'ended_on' => $validated['ended_on'] ?? null,
                    'document_path' => $validated['document_path'] ?? null,
                ],
                $request->user()
            );
        } catch (QueryException $e) {
            // Capture de la contrainte PostgreSQL EXCLUDE lot_ownerships_no_overlap
            if (str_contains($e->getMessage(), 'lot_ownerships_no_overlap') || $e->getCode() === '23P01') {
                return redirect()->back()
                    ->withErrors(['owner_id' => 'Ce copropriétaire possède déjà une détention active chevauchant cette période sur ce lot.'])
                    ->withInput();
            }

            throw $e;
        } catch (InvalidArgumentException $e) {
            $field = str_contains($e->getMessage(), 'chevaucher') ? 'owner_id' : 'quote_part';

            return redirect()->back()
                ->withErrors([$field => $e->getMessage()])
                ->withInput();
        }

        return redirect()->back()->with('success', 'La détention a été enregistrée avec succès.');
    }

    /**
     * Mutate a lot to new incoming owners at an effective date.
     */
    public function mutate(Request $request, Lot $lot, MutateLotAction $action): RedirectResponse
    {
        Gate::authorize('update', $lot);

        $validated = $request->validate([
            'buyer_id' => [
                'required',
                Rule::exists('owners', 'id')->where('residence_id', $lot->residence_id),
            ],
            'effective_date' => ['required', 'date'],
            'prix' => ['nullable', 'integer', 'min:0'],
            'nature' => ['required', Rule::enum(OwnershipNature::class)],
            'quote_part' => ['required', 'integer', 'min:1', 'max:10000'],
            'document_path' => ['nullable', 'string', 'max:255'],
        ], [
            'buyer_id.required' => 'Le nouveau propriétaire acquéreur est obligatoire.',
            'effective_date.required' => 'La date d\'effet de la mutation est obligatoire.',
        ]);

        try {
            $action->handle(
                $lot,
                $validated['effective_date'],
                [
                    [
                        'owner_id' => (int) $validated['buyer_id'],
                        'quote_part' => (int) $validated['quote_part'],
                        'nature' => $validated['nature'],
                        'document_path' => $validated['document_path'] ?? null,
                    ],
                ],
                prix: isset($validated['prix']) ? (int) $validated['prix'] : null,
                documentPath: $validated['document_path'] ?? null,
                actor: $request->user()
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->withErrors(['effective_date' => $e->getMessage()])
                ->withInput();
        }

        return redirect()->back()->with('success', 'La mutation du lot a été enregistrée avec succès.');
    }
}

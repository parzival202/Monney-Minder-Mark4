<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlannedCashFlowRequest;
use App\Http\Requests\UpdatePlannedCashFlowRequest;
use App\Models\PlannedCashFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlannedCashFlowController extends Controller
{
    public function store(StorePlannedCashFlowRequest $request): RedirectResponse
    {
        $request->user()->plannedCashFlows()->create([
            ...$request->validated(),
            'status' => 'planned',
            'source' => 'web',
        ]);

        return back()->with('success', 'Élément planifié ajouté.');
    }

    public function update(UpdatePlannedCashFlowRequest $request, PlannedCashFlow $plannedCashFlow): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $plannedCashFlow);
        $plannedCashFlow->update($request->validated());

        return back()->with('success', 'Élément planifié mis à jour.');
    }

    public function destroy(Request $request, PlannedCashFlow $plannedCashFlow): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $plannedCashFlow);
        $plannedCashFlow->delete();

        return back()->with('success', 'Élément planifié supprimé.');
    }

    private function ensureOwner(int $userId, PlannedCashFlow $cashFlow): void
    {
        abort_unless($cashFlow->user_id === $userId, 404);
    }
}

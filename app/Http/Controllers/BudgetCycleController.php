<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BudgetCycleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cycle_start_day' => ['required', 'integer', 'between:1,28'],
            'monthly_budget_amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'cycle_budget_renews_automatically' => ['required', 'boolean'],
        ]);

        $request->user()->financialProfile()->update($data);

        return back()->with('success', 'Cycle budgétaire mis à jour. Tous les indicateurs ont été recalculés.');
    }
}

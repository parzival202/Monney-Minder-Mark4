<?php

namespace App\Http\Controllers;

use App\Services\Finance\FinancialPositionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FinanceOverviewController extends Controller
{
    public function __invoke(Request $request, FinancialPositionService $positions): Response|RedirectResponse
    {
        $user = $request->user();
        if (!$user->onboarding_completed_at) {
            return redirect()->route('onboarding.financial.show');
        }

        return Inertia::render('Finance/Index', [
            'position' => $positions->forUser($user),
            'accounts' => $user->financialAccounts()->orderByDesc('included_in_planning')->orderBy('name')->get(),
            'cashFlows' => $user->plannedCashFlows()->where('status', 'planned')->orderBy('due_on')->get(),
            'budgetCycle' => $user->financialProfile()->first()->only(['cycle_start_day', 'monthly_budget_amount', 'cycle_budget_renews_automatically']),
        ]);
    }
}

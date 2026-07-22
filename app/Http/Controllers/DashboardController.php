<?php

namespace App\Http\Controllers;

use App\Services\Finance\FinancialPositionService;
use App\Services\Finance\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FinancialPositionService $positions, DashboardMetricsService $metrics): Response|RedirectResponse
    {
        if (!$request->user()->onboarding_completed_at) {
            return redirect()->route('onboarding.financial.show');
        }

        return Inertia::render('Dashboard', [
            'position' => $positions->forUser($request->user()),
            'metrics' => $metrics->forUser($request->user()),
        ]);
    }
}

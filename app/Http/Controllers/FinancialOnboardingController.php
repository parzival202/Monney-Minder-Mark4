<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialOnboardingRequest;
use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\PlannedCashFlow;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FinancialOnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Financial', [
            'defaults' => [
                'account_name' => 'Compte principal',
                'guard_mode' => 'balanced',
                'next_income_on' => now()->addMonth()->format('Y-m-d'),
            ],
        ]);
    }

    public function store(StoreFinancialOnboardingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($data, $user): void {
            FinancialProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'currency' => 'XOF',
                    'timezone' => 'Africa/Abidjan',
                    'protected_savings_amount' => $data['protected_savings'],
                    'safety_buffer_amount' => $data['safety_buffer'],
                    'essential_daily_target_amount' => $data['essential_daily_target'],
                    'guard_mode' => $data['guard_mode'],
                    'onboarding_completed_at' => now(),
                ],
            );

            FinancialAccount::query()->create([
                'user_id' => $user->id,
                'name' => $data['account_name'],
                'type' => 'main',
                'opening_balance_amount' => $data['opening_balance'],
                'opened_on' => today(),
            ]);

            if ($data['expected_income'] > 0) {
                PlannedCashFlow::query()->create([
                    'user_id' => $user->id,
                    'direction' => 'income',
                    'label' => 'Prochaine rentrée d’argent',
                    'amount' => $data['expected_income'],
                    'due_on' => $data['next_income_on'],
                    'source' => 'onboarding',
                ]);
            }

            if ($data['commitments_before_income'] > 0) {
                PlannedCashFlow::query()->create([
                    'user_id' => $user->id,
                    'direction' => 'expense',
                    'label' => 'Charges restantes avant la prochaine rentrée',
                    'amount' => $data['commitments_before_income'],
                    'due_on' => $data['next_income_on'],
                    'is_essential' => true,
                    'source' => 'onboarding',
                ]);
            }

            $user->forceFill(['onboarding_completed_at' => now()])->save();
        });

        return redirect()->route('dashboard')->with('success', 'Votre situation financière initiale est prête.');
    }
}

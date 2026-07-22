<?php

namespace App\Services\Finance;

use App\Models\User;

final class FinancialPositionService
{
    public function __construct(private BudgetCycleService $cycles) {}

    public function forUser(User $user): array
    {
        $profile = $user->financialProfile()->firstOrFail();
        $accountBalance = (int) $user->financialAccounts()
            ->where('included_in_planning', true)
            ->where('is_active', true)
            ->sum('opening_balance_amount');
        $includedTransactions = $user->transactions()->whereHas(
            'financialAccount',
            fn ($query) => $query->where('included_in_planning', true)->where('is_active', true),
        );
        $incomeTransactions = (int) (clone $includedTransactions)->where('type', 'income')->sum('amount');
        $expenseTransactions = (int) (clone $includedTransactions)->where('type', 'expense')->sum('amount');
        $currentBalance = $accountBalance + $incomeTransactions - $expenseTransactions;

        $nextIncome = $user->plannedCashFlows()
            ->where('direction', 'income')
            ->where('status', 'planned')
            ->whereDate('due_on', '>=', today())
            ->orderBy('due_on')
            ->first();

        $horizon = $nextIncome?->due_on ?? today()->addMonth();
        $commitments = (int) $user->plannedCashFlows()
            ->where('direction', 'expense')
            ->where('status', 'planned')
            ->whereDate('due_on', '<=', $horizon)
            ->sum('amount');
        $reservations = (int) $user->reservations()->where('status', 'active')->sum('amount');

        $rawSpendable = $currentBalance
            - $commitments
            - (int) $profile->protected_savings_amount
            - (int) $profile->safety_buffer_amount
            - $reservations;
        $cycle = $this->cycles->forUser($user);
        $cycleExpenses = (int) $user->transactions()->where('type', 'expense')
            ->whereBetween('occurred_on', [$cycle['start'], $cycle['end']])->sum('amount');
        $cycleRemaining = $cycle['budget'] > 0 ? max($cycle['budget'] - $cycleExpenses, 0) : null;
        $cashSpendable = max($rawSpendable, 0);
        $spendable = $cycleRemaining === null ? $cashSpendable : min($cashSpendable, $cycleRemaining);
        $shortfall = abs(min($rawSpendable, 0));
        $daysToCover = $cycle['days_remaining'];
        $dailyAvailable = intdiv($spendable, $daysToCover);

        return [
            'currency' => $profile->currency,
            'current_balance' => $currentBalance,
            'commitments' => $commitments,
            'protected_savings' => (int) $profile->protected_savings_amount,
            'safety_buffer' => (int) $profile->safety_buffer_amount,
            'reservations' => $reservations,
            'spendable' => $spendable,
            'shortfall' => $shortfall,
            'daily_available' => $dailyAvailable,
            'essential_daily_target' => (int) $profile->essential_daily_target_amount,
            'days_to_cover' => $daysToCover,
            'horizon' => $horizon->format('Y-m-d'),
            'guard_mode' => $profile->guard_mode,
            'cycle_budget' => $cycle['budget'],
            'cycle_spent' => $cycleExpenses,
            'cycle_remaining' => $cycleRemaining ?? $cashSpendable,
            'cycle_start' => $cycle['start']->format('Y-m-d'),
            'cycle_end' => $cycle['end']->format('Y-m-d'),
        ];
    }
}

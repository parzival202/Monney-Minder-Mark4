<?php

namespace App\Services\Finance;

use App\Models\User;
use Carbon\CarbonPeriod;

final class DashboardMetricsService
{
    public function __construct(private BudgetCycleService $cycles) {}

    public function forUser(User $user): array
    {
        $cycle = $this->cycles->forUser($user);
        $monthStart = $cycle['start'];
        $monthEnd = $cycle['end'];
        $previousStart = $cycle['previous_start'];
        $previousEnd = $cycle['previous_end'];
        $month = $user->transactions()->with('expenseCategory:id,name,color')->where('type', 'expense')
            ->whereBetween('occurred_on', [$monthStart, $monthEnd])->orderByDesc('occurred_on')->orderByDesc('id')->get();
        $previousTotal = (int) $user->transactions()->where('type', 'expense')->whereBetween('occurred_on', [$previousStart, $previousEnd])->sum('amount');
        $monthTotal = (int) $month->sum('amount');
        $todayTotal = (int) $month->where(fn ($expense) => $expense->occurred_on->isToday())->sum('amount');
        $impulsiveTotal = (int) $month->where('purchase_nature', 'impulsive')->sum('amount');
        $essentialTotal = (int) $month->where('is_essential', true)->sum('amount');
        $elapsedDays = $cycle['days_elapsed'];

        $categories = $month->groupBy(fn ($expense) => $expense->expense_category_id ?: $expense->category)
            ->map(function ($expenses) use ($monthTotal): array {
                $first = $expenses->first();
                $amount = (int) $expenses->sum('amount');
                return [
                    'name' => $first->expenseCategory?->name ?? $first->category ?? 'Sans catégorie',
                    'color' => $first->expenseCategory?->color ?? '#8b8699',
                    'amount' => $amount,
                    'percentage' => $monthTotal > 0 ? round($amount / $monthTotal * 100, 1) : 0,
                    'count' => $expenses->count(),
                ];
            })->sortByDesc('amount')->values()->take(6)->all();

        $trendStart = today()->subDays(29);
        $trendExpenses = $user->transactions()->where('type', 'expense')->whereBetween('occurred_on', [$trendStart, today()])->get();
        $dailyTotals = $trendExpenses->groupBy(fn ($expense) => $expense->occurred_on->format('Y-m-d'))->map->sum('amount');
        $trend = collect(CarbonPeriod::create($trendStart, today()))->map(fn ($date) => [
            'date' => $date->format('Y-m-d'), 'label' => $date->format('d/m'), 'amount' => (int) ($dailyTotals[$date->format('Y-m-d')] ?? 0),
        ])->values()->all();

        return [
            'cycle' => [
                'start' => $monthStart->format('Y-m-d'),
                'end' => $monthEnd->format('Y-m-d'),
                'days_remaining' => $cycle['days_remaining'],
                'budget' => $cycle['budget'],
                'remaining' => max($cycle['budget'] - $monthTotal, 0),
                'used_percentage' => $cycle['budget'] > 0 ? round(min($monthTotal / $cycle['budget'] * 100, 100), 1) : 0,
                'over_budget' => max($monthTotal - $cycle['budget'], 0),
            ],
            'month_total' => $monthTotal,
            'previous_month_total' => $previousTotal,
            'month_change_percentage' => $previousTotal > 0 ? round(($monthTotal - $previousTotal) / $previousTotal * 100, 1) : null,
            'today_total' => $todayTotal,
            'daily_average' => intdiv($monthTotal, $elapsedDays),
            'impulsive_total' => $impulsiveTotal,
            'impulsive_rate' => $monthTotal > 0 ? round($impulsiveTotal / $monthTotal * 100, 1) : 0,
            'essential_total' => $essentialTotal,
            'transaction_count' => $month->count(),
            'no_spend_days' => $elapsedDays - $month->groupBy(fn ($expense) => $expense->occurred_on->format('Y-m-d'))->count(),
            'largest_expense' => $month->sortByDesc('amount')->first()?->only(['description', 'amount', 'category', 'occurred_on']),
            'categories' => $categories,
            'trend' => $trend,
            'recent_expenses' => $month->take(6)->map(fn ($expense) => [
                'id' => $expense->id, 'description' => $expense->description, 'category' => $expense->expenseCategory?->name ?? $expense->category,
                'color' => $expense->expenseCategory?->color ?? '#8b8699', 'amount' => (int) $expense->amount,
                'occurred_on' => $expense->occurred_on->format('Y-m-d'), 'purchase_nature' => $expense->purchase_nature,
            ])->values()->all(),
        ];
    }
}

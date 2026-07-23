<?php

namespace App\Services\Finance;

use App\Models\BudgetCycleArchive;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class BudgetCycleArchiveService
{
    public function __construct(private BudgetCycleService $cycles) {}

    public function archive(User $user, CarbonImmutable $start, CarbonImmutable $end, bool $automatic = false): BudgetCycleArchive
    {
        $expenses = $user->transactions()
            ->with(['financialAccount:id,name', 'expenseCategory:id,name'])
            ->where('type', 'expense')
            ->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()])
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        $spent = (int) $expenses->sum('amount');
        $budget = (int) $user->financialProfile()->value('monthly_budget_amount');
        $categories = $expenses
            ->groupBy(fn ($expense) => $expense->expenseCategory?->name ?? $expense->category ?? 'Sans catégorie')
            ->map(fn (Collection $items, string $name) => [
                'name' => $name,
                'amount' => (int) $items->sum('amount'),
                'count' => $items->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $projects = $user->spendingProjects()
            ->with('activeReservation')
            ->where(function ($query) use ($start, $end): void {
                $query->where(function ($target) use ($start, $end): void {
                    $target->whereDate('target_date', '>=', $start->toDateString())
                        ->whereDate('target_date', '<=', $end->toDateString());
                })
                    ->orWhereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);
            })
            ->orderBy('target_date')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'target_amount' => (int) $project->target_amount,
                'target_date' => $project->target_date->toDateString(),
                'status' => $project->status,
                'source' => $project->source,
                'reserved_amount' => (int) ($project->activeReservation?->amount ?? 0),
            ])->all();

        $archive = BudgetCycleArchive::query()
            ->where('user_id', $user->id)
            ->whereDate('cycle_start', $start->toDateString())
            ->whereDate('cycle_end', $end->toDateString())
            ->first() ?? new BudgetCycleArchive([
                'user_id' => $user->id,
                'cycle_start' => $start->toDateString(),
                'cycle_end' => $end->toDateString(),
            ]);

        $archive->fill([
                'budget_amount' => $budget,
                'total_spent_amount' => $spent,
                'remaining_amount' => max($budget - $spent, 0),
                'overspent_amount' => max($spent - $budget, 0),
                'impulsive_amount' => (int) $expenses->where('purchase_nature', 'impulsive')->sum('amount'),
                'essential_amount' => (int) $expenses->where('is_essential', true)->sum('amount'),
                'transaction_count' => $expenses->count(),
                'no_spend_days' => max($start->diffInDays($end) + 1 - $expenses->groupBy(fn ($expense) => $expense->occurred_on->format('Y-m-d'))->count(), 0),
                'categories' => $categories,
                'projects' => $projects,
                'transactions' => $expenses->map(fn ($expense) => [
                    'id' => $expense->id,
                    'description' => $expense->description,
                    'amount' => (int) $expense->amount,
                    'occurred_on' => $expense->occurred_on->toDateString(),
                    'category' => $expense->expenseCategory?->name ?? $expense->category,
                    'account' => $expense->financialAccount?->name,
                    'purchase_nature' => $expense->purchase_nature,
                    'source' => $expense->source,
                ])->all(),
                'archived_automatically' => $automatic,
                'archived_at' => now(),
            ])->save();

        return $archive;
    }

    public function archivePreviousCycle(User $user, bool $automatic = true): BudgetCycleArchive
    {
        $cycle = $this->cycles->forUser($user);

        return $this->archive($user, $cycle['previous_start'], $cycle['previous_end'], $automatic);
    }
}

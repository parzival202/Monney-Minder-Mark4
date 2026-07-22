<?php

namespace App\Services\Projects;

use App\Domain\Decisions\DecisionEngine;
use App\Domain\Decisions\DecisionInput;
use App\Models\DecisionAssessment;
use App\Models\SpendingProject;
use App\Models\User;

final class ProjectAssessmentService
{
    public function __construct(private readonly DecisionEngine $engine) {}

    public function assess(User $user, SpendingProject $project, string $source = 'web'): DecisionAssessment
    {
        $profile = $user->financialProfile()->firstOrFail();
        $includedTransactions = $user->transactions()->whereHas(
            'financialAccount',
            fn ($query) => $query->where('included_in_planning', true)->where('is_active', true),
        );
        $balance = (int) $user->financialAccounts()->where('included_in_planning', true)->where('is_active', true)->sum('opening_balance_amount')
            + (int) (clone $includedTransactions)->where('type', 'income')->sum('amount')
            - (int) (clone $includedTransactions)->where('type', 'expense')->sum('amount');

        $nextIncomeAfterProject = $user->plannedCashFlows()
            ->where('direction', 'income')
            ->where('status', 'planned')
            ->whereDate('due_on', '>', $project->target_date)
            ->orderBy('due_on')
            ->first();
        $horizon = $nextIncomeAfterProject?->due_on ?? $project->target_date->copy()->addDays(30);
        $confirmedIncome = (int) $user->plannedCashFlows()
            ->where('direction', 'income')
            ->where('status', 'planned')
            ->whereDate('due_on', '>=', today())
            ->whereDate('due_on', '<=', $project->target_date)
            ->sum('amount');
        $commitments = (int) $user->plannedCashFlows()
            ->where('direction', 'expense')
            ->where('status', 'planned')
            ->whereDate('due_on', '>=', today())
            ->whereDate('due_on', '<=', $horizon)
            ->sum('amount');
        $reservations = (int) $user->reservations()->where('status', 'active')->where('spending_project_id', '!=', $project->id)->sum('amount');
        $days = max((int) today()->diffInDays($horizon), 1);

        $result = $this->engine->assess(new DecisionInput(
            currentBalance: $balance,
            confirmedIncome: $confirmedIncome,
            upcomingCommitments: $commitments,
            protectedSavings: (int) $profile->protected_savings_amount,
            existingReservations: $reservations,
            safetyBuffer: (int) $profile->safety_buffer_amount,
            requestedAmount: (int) $project->target_amount,
            daysToCover: $days,
            essentialDailyAmount: (int) $profile->essential_daily_target_amount,
        ));

        return $project->assessments()->create([
            'user_id' => $user->id,
            'requested_amount' => $project->target_amount,
            'target_date' => $project->target_date,
            'verdict' => $result->verdict,
            'available_before_amount' => $result->availableBefore,
            'available_after_amount' => $result->availableAfter,
            'daily_after_amount' => $result->dailyAfter,
            'recommended_daily_amount' => $profile->essential_daily_target_amount,
            'recommended_max_amount' => $result->recommendedMaximum,
            'days_to_cover' => $days,
            'summary' => $result->summary,
            'calculation_snapshot' => [...$result->snapshot, 'horizon' => $horizon->format('Y-m-d')],
            'source' => $source,
        ]);
    }
}

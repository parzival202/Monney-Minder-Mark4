<?php

namespace App\Services\Finance;

use App\Models\User;
use Carbon\CarbonImmutable;

final class BudgetCycleService
{
    public function forUser(User $user, ?CarbonImmutable $date = null): array
    {
        $profile = $user->financialProfile()->firstOrFail();
        $today = $date ?? CarbonImmutable::today();
        $day = min(max((int) $profile->cycle_start_day, 1), 28);
        $start = $today->day >= $day
            ? $today->startOfMonth()->day($day)
            : $today->subMonthNoOverflow()->startOfMonth()->day($day);
        $end = $start->addMonthNoOverflow()->subDay();
        $previousEnd = $start->subDay();
        $previousStart = $previousEnd->startOfMonth()->day($day);
        if ($previousStart->isAfter($previousEnd)) {
            $previousStart = $previousStart->subMonthNoOverflow();
        }

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'days_total' => $start->diffInDays($end) + 1,
            'days_elapsed' => $start->diffInDays($today) + 1,
            'days_remaining' => max($today->diffInDays($end) + 1, 1),
            'budget' => (int) $profile->monthly_budget_amount,
            'start_day' => $day,
        ];
    }
}

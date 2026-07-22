<?php

namespace Tests\Unit\Domain;

use App\Domain\Decisions\DecisionEngine;
use App\Domain\Decisions\DecisionInput;
use PHPUnit\Framework\TestCase;

class DecisionEngineTest extends TestCase
{
    public function test_it_rejects_a_project_that_breaks_the_daily_essential_floor(): void
    {
        $result = (new DecisionEngine())->assess(new DecisionInput(
            currentBalance: 100_000,
            confirmedIncome: 0,
            upcomingCommitments: 30_000,
            protectedSavings: 10_000,
            existingReservations: 10_000,
            safetyBuffer: 0,
            requestedAmount: 40_000,
            daysToCover: 20,
            essentialDailyAmount: 3_000,
        ));

        $this->assertSame('impossible', $result->verdict);
        $this->assertSame(500, $result->dailyAfter);
        $this->assertSame(0, $result->recommendedMaximum);
    }

    public function test_it_approves_a_project_with_a_healthy_margin(): void
    {
        $result = (new DecisionEngine())->assess(new DecisionInput(
            currentBalance: 250_000,
            confirmedIncome: 0,
            upcomingCommitments: 40_000,
            protectedSavings: 20_000,
            existingReservations: 10_000,
            safetyBuffer: 20_000,
            requestedAmount: 40_000,
            daysToCover: 20,
            essentialDailyAmount: 3_000,
        ));

        $this->assertSame('approved', $result->verdict);
        $this->assertSame(6_000, $result->dailyAfter);
        $this->assertSame(100_000, $result->recommendedMaximum);
    }

    public function test_existing_reservations_reduce_the_available_amount(): void
    {
        $withoutReservation = (new DecisionEngine())->assess(new DecisionInput(
            150_000, 0, 20_000, 10_000, 0, 10_000, 20_000, 20, 3_000,
        ));
        $withReservation = (new DecisionEngine())->assess(new DecisionInput(
            150_000, 0, 20_000, 10_000, 40_000, 10_000, 20_000, 20, 3_000,
        ));

        $this->assertSame(40_000, $withoutReservation->availableAfter - $withReservation->availableAfter);
    }
}

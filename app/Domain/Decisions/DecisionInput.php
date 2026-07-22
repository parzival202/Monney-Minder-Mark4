<?php

namespace App\Domain\Decisions;

use InvalidArgumentException;

final readonly class DecisionInput
{
    public function __construct(
        public int $currentBalance,
        public int $confirmedIncome,
        public int $upcomingCommitments,
        public int $protectedSavings,
        public int $existingReservations,
        public int $safetyBuffer,
        public int $requestedAmount,
        public int $daysToCover,
        public int $essentialDailyAmount,
    ) {
        if ($this->requestedAmount < 0 || $this->daysToCover < 1 || $this->essentialDailyAmount < 0) {
            throw new InvalidArgumentException('Les montants doivent être positifs et la période doit couvrir au moins un jour.');
        }
    }
}

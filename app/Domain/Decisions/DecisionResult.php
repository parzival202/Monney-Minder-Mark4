<?php

namespace App\Domain\Decisions;

final readonly class DecisionResult
{
    public function __construct(
        public string $verdict,
        public int $availableBefore,
        public int $availableAfter,
        public int $dailyAfter,
        public int $recommendedMaximum,
        public string $summary,
        public array $snapshot,
    ) {}
}

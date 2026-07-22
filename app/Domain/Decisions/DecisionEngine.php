<?php

namespace App\Domain\Decisions;

final class DecisionEngine
{
    public function assess(DecisionInput $input): DecisionResult
    {
        $availableBefore = $input->currentBalance
            + $input->confirmedIncome
            - $input->upcomingCommitments
            - $input->protectedSavings
            - $input->existingReservations
            - $input->safetyBuffer;

        $availableAfter = $availableBefore - $input->requestedAmount;
        $dailyAfter = intdiv($availableAfter, $input->daysToCover);
        $essentialNeed = $input->essentialDailyAmount * $input->daysToCover;
        $recommendedMaximum = max(0, $availableBefore - $essentialNeed);

        [$verdict, $summary] = $this->verdict(
            $availableAfter,
            $dailyAfter,
            $input->essentialDailyAmount,
            $recommendedMaximum,
        );

        return new DecisionResult(
            verdict: $verdict,
            availableBefore: $availableBefore,
            availableAfter: $availableAfter,
            dailyAfter: $dailyAfter,
            recommendedMaximum: $recommendedMaximum,
            summary: $summary,
            snapshot: [
                'current_balance' => $input->currentBalance,
                'confirmed_income' => $input->confirmedIncome,
                'upcoming_commitments' => $input->upcomingCommitments,
                'protected_savings' => $input->protectedSavings,
                'existing_reservations' => $input->existingReservations,
                'safety_buffer' => $input->safetyBuffer,
                'requested_amount' => $input->requestedAmount,
                'days_to_cover' => $input->daysToCover,
                'essential_daily_amount' => $input->essentialDailyAmount,
            ],
        );
    }

    private function verdict(int $availableAfter, int $dailyAfter, int $essentialDailyAmount, int $recommendedMaximum): array
    {
        $displayDailyAfter = max($dailyAfter, 0);

        if ($availableAfter < 0 || ($essentialDailyAmount > 0 && $dailyAfter < (int) floor($essentialDailyAmount * 0.5))) {
            return ['impossible', "Cette dépense épuiserait ton disponible quotidien (0), très loin du besoin essentiel de {$essentialDailyAmount}."];
        }

        if ($essentialDailyAmount > 0 && $dailyAfter < $essentialDailyAmount) {
            return ['risky', "Cette dépense laisserait {$displayDailyAfter} par jour, sous le besoin essentiel de {$essentialDailyAmount}. Le maximum recommandé est {$recommendedMaximum}."];
        }

        if ($essentialDailyAmount > 0 && $dailyAfter < (int) ceil($essentialDailyAmount * 1.25)) {
            return ['caution', "Cette dépense reste possible, mais la marge quotidienne serait limitée à {$displayDailyAfter}."];
        }

        return ['approved', "Cette dépense est compatible avec le plan actuel et laisse {$displayDailyAfter} disponibles par jour."];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContributeReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\SpendingProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, SpendingProject $project): RedirectResponse
    {
        if ($project->activeReservation()->exists()) {
            return back()->withErrors(['reservation' => 'Ce projet possède déjà une réservation active.']);
        }

        $assessment = $project->latestAssessment()->first();
        if (!$assessment || !in_array($assessment->verdict, ['approved', 'caution'], true)) {
            return back()->withErrors(['reservation' => 'Ce projet doit être compatible ou réalisable avec prudence avant de réserver son budget.']);
        }

        $data = $request->validated();
        $amount = $data['strategy'] === 'immediate'
            ? (int) $project->target_amount
            : min((int) $data['initial_amount'], (int) $project->target_amount);

        $project->reservations()->create([
            'user_id' => $request->user()->id,
            'amount' => $amount,
            'target_amount' => $project->target_amount,
            'strategy' => $data['strategy'],
            'contribution_frequency' => $data['strategy'] === 'progressive' ? $data['contribution_frequency'] : null,
            'status' => 'active',
            'reserved_on' => today(),
            'next_contribution_on' => $data['strategy'] === 'progressive'
                ? ($data['contribution_frequency'] === 'weekly' ? today()->addWeek() : today()->addMonth())
                : null,
        ]);
        $project->update(['status' => $amount >= $project->target_amount ? 'reserved' : 'saving']);

        return back()->with('success', $data['strategy'] === 'immediate' ? 'Budget entièrement réservé.' : 'Plan de réservation progressive démarré.');
    }

    public function contribute(ContributeReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->status === 'active', 422);
        $newAmount = min($reservation->target_amount, $reservation->amount + (int) $request->validated('amount'));
        $nextDate = $reservation->contribution_frequency === 'weekly' ? today()->addWeek() : today()->addMonth();
        $reservation->update(['amount' => $newAmount, 'next_contribution_on' => $newAmount >= $reservation->target_amount ? null : $nextDate]);
        $reservation->spendingProject()->update(['status' => $newAmount >= $reservation->target_amount ? 'reserved' : 'saving']);

        return back()->with('success', 'Contribution ajoutée à la réservation.');
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 404);
        $reservation->update(['status' => 'released', 'released_on' => today(), 'next_contribution_on' => null]);
        $reservation->spendingProject()->update(['status' => 'assessed']);

        return back()->with('success', 'Réservation annulée : la somme est de nouveau disponible.');
    }
}

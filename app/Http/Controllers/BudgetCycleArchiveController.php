<?php

namespace App\Http\Controllers;

use App\Services\Finance\BudgetCycleArchiveService;
use App\Services\Finance\BudgetCycleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetCycleArchiveController extends Controller
{
    public function index(Request $request, BudgetCycleService $cycles): Response
    {
        $user = $request->user();
        $cycle = $cycles->forUser($user);
        $archivedStarts = $user->budgetCycleArchives()->pluck('cycle_start')->map(fn ($date) => CarbonImmutable::parse($date)->toDateString());
        $earliest = $user->transactions()->min('occurred_on');
        $cursor = $earliest
            ? $cycles->forUser($user, CarbonImmutable::parse($earliest))['start']
            : $cycle['previous_start'];
        $available = [];

        while ($cursor->lessThanOrEqualTo($cycle['previous_start'])) {
            $end = $cursor->addMonthNoOverflow()->subDay();
            if (!$archivedStarts->contains($cursor->toDateString())) {
                $available[] = [
                    'start' => $cursor->toDateString(),
                    'end' => $end->toDateString(),
                    'label' => $cursor->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'),
                ];
            }
            $cursor = $cursor->addMonthNoOverflow();
        }

        return Inertia::render('Archives/Index', [
            'automatic' => (bool) $user->financialProfile()->value('auto_archive_cycles'),
            'availableCycles' => array_reverse($available),
            'archives' => $user->budgetCycleArchives()->latest('cycle_start')->get()->map(fn ($archive) => [
                'id' => $archive->id,
                'cycle_start' => $archive->cycle_start->toDateString(),
                'cycle_end' => $archive->cycle_end->toDateString(),
                'budget_amount' => $archive->budget_amount,
                'total_spent_amount' => $archive->total_spent_amount,
                'remaining_amount' => $archive->remaining_amount,
                'overspent_amount' => $archive->overspent_amount,
                'impulsive_amount' => $archive->impulsive_amount,
                'essential_amount' => $archive->essential_amount,
                'transaction_count' => $archive->transaction_count,
                'no_spend_days' => $archive->no_spend_days,
                'categories' => $archive->categories ?? [],
                'projects' => $archive->projects ?? [],
                'transactions' => $archive->transactions ?? [],
                'archived_automatically' => $archive->archived_automatically,
                'archived_at' => $archive->archived_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request, BudgetCycleService $cycles, BudgetCycleArchiveService $archives): RedirectResponse
    {
        $data = $request->validate(['cycle_start' => ['required', 'date']]);
        $user = $request->user();
        $start = CarbonImmutable::parse($data['cycle_start'])->startOfDay();
        $expected = $cycles->forUser($user, $start);
        abort_unless($start->isSameDay($expected['start']) && $expected['end']->isBefore(today()), 422);

        $archives->archive($user, $expected['start'], $expected['end']);

        return back()->with('success', 'Cycle archivé. Les données d’origine restent intactes.');
    }

    public function updatePreference(Request $request): RedirectResponse
    {
        $data = $request->validate(['automatic' => ['required', 'boolean']]);
        $request->user()->financialProfile()->update(['auto_archive_cycles' => $data['automatic']]);

        return back()->with('success', $data['automatic'] ? 'Archivage automatique activé.' : 'Archivage automatique désactivé.');
    }
}

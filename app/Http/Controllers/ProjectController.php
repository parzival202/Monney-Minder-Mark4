<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\SpendingProject;
use App\Services\Finance\FinancialPositionService;
use App\Services\Projects\ProjectAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, FinancialPositionService $positions): Response|RedirectResponse
    {
        $user = $request->user();
        if (!$user->onboarding_completed_at) {
            return redirect()->route('onboarding.financial.show');
        }

        return Inertia::render('Projects/Index', [
            'position' => $positions->forUser($user),
            'projects' => $user->spendingProjects()
                ->with(['latestAssessment', 'activeReservation'])
                ->orderByRaw("CASE WHEN status IN ('reserved', 'saving') THEN 0 ELSE 1 END")
                ->orderBy('target_date')
                ->get(),
        ]);
    }

    public function store(StoreProjectRequest $request, ProjectAssessmentService $assessments): RedirectResponse
    {
        DB::transaction(function () use ($request, $assessments): void {
            $project = $request->user()->spendingProjects()->create([
                ...$request->validated(),
                'status' => 'assessed',
                'source' => 'web',
            ]);
            $assessments->assess($request->user(), $project);
        });

        return back()->with('success', 'Projet évalué avec ta situation financière actuelle.');
    }

    public function update(UpdateProjectRequest $request, SpendingProject $project, ProjectAssessmentService $assessments): RedirectResponse
    {
        DB::transaction(function () use ($request, $project, $assessments): void {
            $project->update($request->validated());
            if ($reservation = $project->activeReservation()->first()) {
                $reservation->update([
                    'target_amount' => $project->target_amount,
                    'amount' => min($reservation->amount, $project->target_amount),
                ]);
            }
            $assessments->assess($request->user(), $project);
        });

        return back()->with('success', 'Projet modifié et réévalué.');
    }

    public function reassess(Request $request, SpendingProject $project, ProjectAssessmentService $assessments): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $project);
        $assessments->assess($request->user(), $project);

        return back()->with('success', 'Le projet a été recalculé avec les données actuelles.');
    }

    public function destroy(Request $request, SpendingProject $project): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $project);
        $project->delete();

        return back()->with('success', 'Projet supprimé et sommes réservées libérées.');
    }

    private function ensureOwner(int $userId, SpendingProject $project): void
    {
        abort_unless($project->user_id === $userId, 404);
    }
}

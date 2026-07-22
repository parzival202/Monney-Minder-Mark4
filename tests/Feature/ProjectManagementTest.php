<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\Reservation;
use App\Models\SpendingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private function context(int $balance = 500_000): array
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create([
            'user_id' => $user->id,
            'essential_daily_target_amount' => 3_000,
            'protected_savings_amount' => 20_000,
            'safety_buffer_amount' => 10_000,
        ]);
        FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Principal',
            'opening_balance_amount' => $balance,
        ]);

        return compact('user');
    }

    private function projectPayload(int $amount = 40_000): array
    {
        return [
            'name' => 'Sortie',
            'category' => 'Loisirs',
            'target_amount' => $amount,
            'target_date' => today()->addDays(10)->format('Y-m-d'),
            'priority' => 'want',
            'notes' => 'Projet test',
        ];
    }

    public function test_project_is_immediately_assessed_from_real_financial_data(): void
    {
        ['user' => $user] = $this->context(100_000);

        $this->actingAs($user)->post('/projects', $this->projectPayload(90_000))->assertSessionHasNoErrors();

        $project = SpendingProject::query()->firstOrFail();
        $this->assertDatabaseHas('decision_assessments', [
            'spending_project_id' => $project->id,
            'user_id' => $user->id,
            'verdict' => 'impossible',
            'available_before_amount' => 70_000,
            'available_after_amount' => -20_000,
        ]);
    }

    public function test_approved_project_can_be_reserved_and_reduces_spendable_amount(): void
    {
        ['user' => $user] = $this->context();
        $this->actingAs($user)->post('/projects', $this->projectPayload())->assertSessionHasNoErrors();
        $project = SpendingProject::query()->firstOrFail();

        $this->actingAs($user)->post("/projects/{$project->id}/reservations", [
            'strategy' => 'immediate',
            'initial_amount' => 0,
            'contribution_frequency' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', ['spending_project_id' => $project->id, 'amount' => 40_000, 'status' => 'active']);
        $this->actingAs($user)->get('/projects')->assertInertia(fn ($page) => $page
            ->where('position.reservations', 40_000)
            ->where('position.spendable', 430_000));
    }

    public function test_impossible_project_cannot_reserve_money(): void
    {
        ['user' => $user] = $this->context(100_000);
        $this->actingAs($user)->post('/projects', $this->projectPayload(90_000));
        $project = SpendingProject::query()->firstOrFail();

        $this->actingAs($user)->post("/projects/{$project->id}/reservations", [
            'strategy' => 'immediate',
            'initial_amount' => 0,
        ])->assertSessionHasErrors('reservation');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_progressive_reservation_accepts_capped_contributions_and_can_be_released(): void
    {
        ['user' => $user] = $this->context();
        $this->actingAs($user)->post('/projects', $this->projectPayload());
        $project = SpendingProject::query()->firstOrFail();

        $this->actingAs($user)->post("/projects/{$project->id}/reservations", [
            'strategy' => 'progressive',
            'initial_amount' => 10_000,
            'contribution_frequency' => 'weekly',
        ])->assertSessionHasNoErrors();
        $reservation = Reservation::query()->firstOrFail();

        $this->actingAs($user)->post("/reservations/{$reservation->id}/contributions", ['amount' => 50_000])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'amount' => 40_000, 'status' => 'active']);
        $this->assertDatabaseHas('spending_projects', ['id' => $project->id, 'status' => 'reserved']);

        $this->actingAs($user)->delete("/reservations/{$reservation->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'released']);
        $this->actingAs($user)->get('/projects')->assertInertia(fn ($page) => $page->where('position.reservations', 0));
    }

    public function test_projects_and_reservations_are_private_to_their_owner(): void
    {
        ['user' => $owner] = $this->context();
        ['user' => $other] = $this->context();
        $this->actingAs($owner)->post('/projects', $this->projectPayload());
        $project = SpendingProject::query()->where('user_id', $owner->id)->firstOrFail();
        $this->actingAs($owner)->post("/projects/{$project->id}/reservations", [
            'strategy' => 'progressive',
            'initial_amount' => 5_000,
            'contribution_frequency' => 'monthly',
        ]);
        $reservation = Reservation::query()->firstOrFail();

        $this->actingAs($other)->put("/projects/{$project->id}", $this->projectPayload())->assertForbidden();
        $this->actingAs($other)->post("/projects/{$project->id}/reassess")->assertNotFound();
        $this->actingAs($other)->delete("/projects/{$project->id}")->assertNotFound();
        $this->actingAs($other)->post("/projects/{$project->id}/reservations", [
            'strategy' => 'immediate', 'initial_amount' => 0,
        ])->assertForbidden();
        $this->actingAs($other)->post("/reservations/{$reservation->id}/contributions", ['amount' => 1_000])->assertForbidden();
        $this->actingAs($other)->delete("/reservations/{$reservation->id}")->assertNotFound();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'amount' => 5_000, 'status' => 'active']);
    }
}

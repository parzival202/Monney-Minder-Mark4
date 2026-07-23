<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\SpendingProject;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Finance\BudgetCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCycleArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $automatic = true): User
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create([
            'user_id' => $user->id,
            'cycle_start_day' => 27,
            'monthly_budget_amount' => 100_000,
            'auto_archive_cycles' => $automatic,
        ]);

        return $user;
    }

    public function test_a_completed_cycle_can_be_archived_manually_with_its_expenses_and_telegram_projects(): void
    {
        $user = $this->user();
        $account = FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Principal',
            'opening_balance_amount' => 200_000,
            'included_in_planning' => true,
            'is_active' => true,
        ]);
        $cycle = app(BudgetCycleService::class)->forUser($user);
        Transaction::query()->create([
            'user_id' => $user->id,
            'financial_account_id' => $account->id,
            'type' => 'expense',
            'description' => 'Restaurant',
            'category' => 'Sorties',
            'amount' => 25_000,
            'occurred_on' => $cycle['previous_start']->addDay(),
            'purchase_nature' => 'impulsive',
            'source' => 'telegram',
        ]);
        SpendingProject::query()->create([
            'user_id' => $user->id,
            'name' => 'Sortie plage',
            'target_amount' => 40_000,
            'target_date' => $cycle['previous_end'],
            'status' => 'assessed',
            'source' => 'telegram',
        ]);

        $this->actingAs($user)->post('/archives', [
            'cycle_start' => $cycle['previous_start']->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('budget_cycle_archives', [
            'user_id' => $user->id,
            'total_spent_amount' => 25_000,
            'remaining_amount' => 75_000,
            'transaction_count' => 1,
            'archived_automatically' => false,
        ]);
        $this->actingAs($user)->get('/archives')->assertInertia(fn ($page) => $page
            ->component('Archives/Index')
            ->has('archives', 1)
            ->where('archives.0.projects.0.name', 'Sortie plage')
            ->where('archives.0.projects.0.source', 'telegram')
            ->where('archives.0.transactions.0.description', 'Restaurant'));
        $this->assertDatabaseHas('transactions', ['description' => 'Restaurant']);
        $this->assertDatabaseHas('spending_projects', ['name' => 'Sortie plage']);
    }

    public function test_automatic_archive_is_idempotent_and_respects_the_preference(): void
    {
        $enabled = $this->user();
        $disabled = $this->user(false);

        $this->artisan('money-minder:archive-cycles')->assertSuccessful();
        $this->artisan('money-minder:archive-cycles')->assertSuccessful();

        $this->assertDatabaseCount('budget_cycle_archives', 1);
        $this->assertDatabaseHas('budget_cycle_archives', [
            'user_id' => $enabled->id,
            'archived_automatically' => true,
        ]);
        $this->assertDatabaseMissing('budget_cycle_archives', ['user_id' => $disabled->id]);
    }
}

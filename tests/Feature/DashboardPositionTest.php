<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\PlannedCashFlow;
use App\Models\User;
use App\Models\ExpenseCategory;
use App\Models\Transaction;
use App\Services\Finance\BudgetCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_the_real_spendable_amount(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create([
            'user_id' => $user->id,
            'protected_savings_amount' => 50_000,
            'safety_buffer_amount' => 20_000,
            'essential_daily_target_amount' => 3_000,
        ]);
        FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Principal',
            'opening_balance_amount' => 250_000,
        ]);
        PlannedCashFlow::query()->create([
            'user_id' => $user->id,
            'direction' => 'expense',
            'label' => 'Charges',
            'amount' => 40_000,
            'due_on' => today()->addDays(20),
        ]);
        PlannedCashFlow::query()->create([
            'user_id' => $user->id,
            'direction' => 'income',
            'label' => 'Salaire',
            'amount' => 300_000,
            'due_on' => today()->addDays(20),
        ]);

        $cycle = app(BudgetCycleService::class)->forUser($user);
        $expectedDaily = intdiv(140_000, $cycle['days_remaining']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('position.current_balance', 250_000)
                ->where('position.spendable', 140_000)
                ->where('position.daily_available', $expectedDaily));
    }

    public function test_dashboard_never_uses_another_users_money(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $other = User::factory()->create(['onboarding_completed_at' => now()]);

        FinancialProfile::query()->create(['user_id' => $user->id]);
        FinancialAccount::query()->create(['user_id' => $user->id, 'name' => 'Moi', 'opening_balance_amount' => 10_000]);
        FinancialAccount::query()->create(['user_id' => $other->id, 'name' => 'Autre', 'opening_balance_amount' => 999_000]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('position.current_balance', 10_000));
    }

    public function test_dashboard_exposes_real_spending_kpis_trends_and_categories(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create(['user_id' => $user->id, 'essential_daily_target_amount' => 3_000]);
        $account = FinancialAccount::query()->create(['user_id' => $user->id, 'name' => 'Principal', 'opening_balance_amount' => 200_000]);
        $food = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Alimentation', 'color' => '#10b981', 'is_essential' => true]);
        $leisure = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Loisirs', 'color' => '#8b5cf6']);

        Transaction::query()->create(['user_id' => $user->id, 'financial_account_id' => $account->id, 'expense_category_id' => $food->id, 'type' => 'expense', 'category' => 'Alimentation', 'description' => 'Courses', 'amount' => 12_000, 'occurred_on' => today(), 'purchase_nature' => 'planned', 'is_essential' => true]);
        Transaction::query()->create(['user_id' => $user->id, 'financial_account_id' => $account->id, 'expense_category_id' => $leisure->id, 'type' => 'expense', 'category' => 'Loisirs', 'description' => 'Cinéma', 'amount' => 8_000, 'occurred_on' => today()->subDays(2), 'purchase_nature' => 'impulsive']);
        Transaction::query()->create(['user_id' => $user->id, 'financial_account_id' => $account->id, 'expense_category_id' => $food->id, 'type' => 'expense', 'category' => 'Alimentation', 'description' => 'Ancien mois', 'amount' => 10_000, 'occurred_on' => today()->subMonthNoOverflow()->startOfMonth(), 'purchase_nature' => 'planned']);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('metrics.month_total', 20_000)
            ->where('metrics.previous_month_total', 10_000)
            ->where('metrics.month_change_percentage', 100)
            ->where('metrics.today_total', 12_000)
            ->where('metrics.impulsive_total', 8_000)
            ->where('metrics.impulsive_rate', 40)
            ->where('metrics.transaction_count', 2)
            ->where('metrics.categories.0.name', 'Alimentation')
            ->where('metrics.categories.0.amount', 12_000)
            ->where('metrics.categories.1.name', 'Loisirs')
            ->where('metrics.trend.29.amount', 12_000)
            ->where('metrics.recent_expenses.0.description', 'Courses'));
    }

    public function test_spending_kpis_never_include_another_users_expenses(): void
    {
        $mine = User::factory()->create(['onboarding_completed_at' => now()]);
        $other = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create(['user_id' => $mine->id]);
        $myAccount = FinancialAccount::query()->create(['user_id' => $mine->id, 'name' => 'Moi', 'opening_balance_amount' => 20_000]);
        $otherAccount = FinancialAccount::query()->create(['user_id' => $other->id, 'name' => 'Autre', 'opening_balance_amount' => 999_000]);
        Transaction::query()->create(['user_id' => $other->id, 'financial_account_id' => $otherAccount->id, 'type' => 'expense', 'category' => 'Privé', 'description' => 'Dépense privée', 'amount' => 500_000, 'occurred_on' => today()]);

        $this->actingAs($mine)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('metrics.month_total', 0)
            ->where('metrics.transaction_count', 0)
            ->has('metrics.recent_expenses', 0));
    }
}

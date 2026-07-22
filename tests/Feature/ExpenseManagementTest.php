<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create([
            'user_id' => $user->id,
            'essential_daily_target_amount' => 3_000,
        ]);
        $account = FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Principal',
            'opening_balance_amount' => 100_000,
        ]);
        $category = ExpenseCategory::query()->create([
            'user_id' => $user->id,
            'name' => 'Alimentation',
            'color' => '#10b981',
            'is_essential' => true,
        ]);

        return compact('user', 'account', 'category');
    }

    public function test_expense_reduces_the_real_spendable_amount_immediately(): void
    {
        ['user' => $user, 'account' => $account, 'category' => $category] = $this->context();

        $this->actingAs($user)->post('/expenses', [
            'financial_account_id' => $account->id,
            'expense_category_id' => $category->id,
            'description' => 'Déjeuner',
            'amount' => 5_000,
            'occurred_on' => today()->format('Y-m-d'),
            'purchase_nature' => 'impulsive',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 5_000,
            'purchase_nature' => 'impulsive',
            'is_essential' => true,
        ]);
        $this->actingAs($user)
            ->get('/expenses')
            ->assertInertia(fn ($page) => $page
                ->where('position.current_balance', 95_000)
                ->where('position.spendable', 95_000)
                ->where('summary.impulsive_total', 5_000));
    }

    public function test_user_can_update_and_delete_an_expense(): void
    {
        ['user' => $user, 'account' => $account, 'category' => $category] = $this->context();
        $expense = Transaction::query()->create([
            'user_id' => $user->id,
            'financial_account_id' => $account->id,
            'expense_category_id' => $category->id,
            'type' => 'expense',
            'category' => $category->name,
            'description' => 'Ancien',
            'amount' => 10_000,
            'occurred_on' => today(),
        ]);

        $payload = [
            'financial_account_id' => $account->id,
            'expense_category_id' => $category->id,
            'description' => 'Corrigé',
            'amount' => 7_000,
            'occurred_on' => today()->format('Y-m-d'),
            'purchase_nature' => 'planned',
        ];
        $this->actingAs($user)->put("/expenses/{$expense->id}", $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transactions', ['id' => $expense->id, 'amount' => 7_000, 'description' => 'Corrigé']);

        $this->actingAs($user)->delete("/expenses/{$expense->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('transactions', ['id' => $expense->id]);
    }

    public function test_user_cannot_use_or_modify_another_users_account_category_or_expense(): void
    {
        $mine = $this->context();
        $other = $this->context();
        $foreignExpense = Transaction::query()->create([
            'user_id' => $other['user']->id,
            'financial_account_id' => $other['account']->id,
            'expense_category_id' => $other['category']->id,
            'type' => 'expense',
            'category' => $other['category']->name,
            'description' => 'Privé',
            'amount' => 50_000,
            'occurred_on' => today(),
        ]);

        $foreignPayload = [
            'financial_account_id' => $other['account']->id,
            'expense_category_id' => $other['category']->id,
            'description' => 'Tentative',
            'amount' => 1,
            'occurred_on' => today()->format('Y-m-d'),
            'purchase_nature' => 'planned',
        ];

        $this->actingAs($mine['user'])->post('/expenses', $foreignPayload)->assertSessionHasErrors(['financial_account_id', 'expense_category_id']);
        $this->actingAs($mine['user'])->put("/expenses/{$foreignExpense->id}", $foreignPayload)->assertForbidden();
        $this->actingAs($mine['user'])->delete("/expenses/{$foreignExpense->id}")->assertNotFound();
        $this->assertDatabaseHas('transactions', ['id' => $foreignExpense->id, 'amount' => 50_000]);
    }

    public function test_future_and_zero_expenses_are_rejected(): void
    {
        ['user' => $user, 'account' => $account, 'category' => $category] = $this->context();

        $this->actingAs($user)->post('/expenses', [
            'financial_account_id' => $account->id,
            'expense_category_id' => $category->id,
            'description' => 'Invalide',
            'amount' => 0,
            'occurred_on' => today()->addDay()->format('Y-m-d'),
            'purchase_nature' => 'planned',
        ])->assertSessionHasErrors(['amount', 'occurred_on']);
    }

    public function test_used_category_is_archived_without_losing_the_historical_label(): void
    {
        ['user' => $user, 'account' => $account, 'category' => $category] = $this->context();
        Transaction::query()->create([
            'user_id' => $user->id,
            'financial_account_id' => $account->id,
            'expense_category_id' => $category->id,
            'type' => 'expense',
            'category' => $category->name,
            'description' => 'Historique',
            'amount' => 1_000,
            'occurred_on' => today(),
        ]);

        $this->actingAs($user)->delete("/expenses/categories/{$category->id}")->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'is_active' => false]);
        $this->assertDatabaseHas('transactions', ['category' => 'Alimentation']);
    }

    public function test_expense_from_an_excluded_account_does_not_change_the_planning_position(): void
    {
        ['user' => $user, 'category' => $category] = $this->context();
        $excluded = FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Compte hors budget',
            'opening_balance_amount' => 50_000,
            'included_in_planning' => false,
        ]);
        Transaction::query()->create([
            'user_id' => $user->id,
            'financial_account_id' => $excluded->id,
            'expense_category_id' => $category->id,
            'type' => 'expense',
            'category' => $category->name,
            'description' => 'Hors budget suivi',
            'amount' => 25_000,
            'occurred_on' => today(),
        ]);

        $this->actingAs($user)
            ->get('/expenses')
            ->assertInertia(fn ($page) => $page->where('position.current_balance', 100_000));
    }
}

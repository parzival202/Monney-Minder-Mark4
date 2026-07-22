<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\PlannedCashFlow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(): User
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_user_can_create_update_and_delete_a_planned_charge(): void
    {
        $user = $this->onboardedUser();

        $this->actingAs($user)->post('/finances/cash-flows', [
            'direction' => 'expense',
            'label' => 'Loyer',
            'amount' => 75_000,
            'due_on' => today()->addDays(5)->format('Y-m-d'),
            'is_essential' => true,
        ])->assertSessionHasNoErrors();

        $flow = PlannedCashFlow::query()->firstOrFail();
        $this->actingAs($user)->put("/finances/cash-flows/{$flow->id}", [
            'direction' => 'expense',
            'label' => 'Loyer corrigé',
            'amount' => 70_000,
            'due_on' => today()->addDays(6)->format('Y-m-d'),
            'is_essential' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('planned_cash_flows', ['id' => $flow->id, 'amount' => 70_000]);
        $this->actingAs($user)->delete("/finances/cash-flows/{$flow->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('planned_cash_flows', ['id' => $flow->id]);
    }

    public function test_user_cannot_modify_or_delete_another_users_financial_data(): void
    {
        $user = $this->onboardedUser();
        $other = $this->onboardedUser();
        $account = FinancialAccount::query()->create([
            'user_id' => $other->id,
            'name' => 'Compte privé',
            'opening_balance_amount' => 500_000,
        ]);
        $flow = PlannedCashFlow::query()->create([
            'user_id' => $other->id,
            'direction' => 'income',
            'label' => 'Revenu privé',
            'amount' => 500_000,
            'due_on' => today(),
        ]);

        $accountPayload = ['name' => 'Volé', 'type' => 'bank', 'opening_balance_amount' => 1, 'included_in_planning' => true];
        $flowPayload = ['direction' => 'income', 'label' => 'Volé', 'amount' => 1, 'due_on' => today()->format('Y-m-d'), 'is_essential' => false];

        $this->actingAs($user)->put("/finances/accounts/{$account->id}", $accountPayload)->assertNotFound();
        $this->actingAs($user)->delete("/finances/accounts/{$account->id}")->assertNotFound();
        $this->actingAs($user)->put("/finances/cash-flows/{$flow->id}", $flowPayload)->assertNotFound();
        $this->actingAs($user)->delete("/finances/cash-flows/{$flow->id}")->assertNotFound();

        $this->assertDatabaseHas('financial_accounts', ['id' => $account->id, 'name' => 'Compte privé']);
        $this->assertDatabaseHas('planned_cash_flows', ['id' => $flow->id, 'amount' => 500_000]);
    }

    public function test_last_account_cannot_be_deleted(): void
    {
        $user = $this->onboardedUser();
        $account = FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Unique',
            'opening_balance_amount' => 10_000,
        ]);

        $this->actingAs($user)
            ->delete("/finances/accounts/{$account->id}")
            ->assertSessionHasErrors('account');

        $this->assertDatabaseHas('financial_accounts', ['id' => $account->id]);
    }

    public function test_account_excluded_from_planning_does_not_inflate_spendable_money(): void
    {
        $user = $this->onboardedUser();
        FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Principal',
            'opening_balance_amount' => 100_000,
            'included_in_planning' => true,
        ]);
        FinancialAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Hors calcul',
            'opening_balance_amount' => 900_000,
            'included_in_planning' => false,
        ]);

        $this->actingAs($user)
            ->get('/finances')
            ->assertInertia(fn ($page) => $page->where('position.current_balance', 100_000));
    }
}

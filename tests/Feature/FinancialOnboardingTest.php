<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\PlannedCashFlow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_without_a_profile_sees_the_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/onboarding/financial')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Onboarding/Financial'));
    }

    public function test_the_onboarding_creates_an_isolated_financial_starting_position(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/onboarding/financial', [
            'account_name' => 'Compte principal',
            'opening_balance' => 250_000,
            'expected_income' => 300_000,
            'next_income_on' => today()->addDays(20)->format('Y-m-d'),
            'commitments_before_income' => 40_000,
            'protected_savings' => 50_000,
            'safety_buffer' => 20_000,
            'essential_daily_target' => 3_000,
            'guard_mode' => 'balanced',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertNotNull($user->fresh()->onboarding_completed_at);
        $this->assertDatabaseHas(FinancialProfile::class, [
            'user_id' => $user->id,
            'protected_savings_amount' => 50_000,
        ]);
        $this->assertDatabaseHas(FinancialAccount::class, [
            'user_id' => $user->id,
            'opening_balance_amount' => 250_000,
        ]);
        $this->assertDatabaseCount(PlannedCashFlow::class, 2);
    }

    public function test_invalid_or_negative_money_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/onboarding/financial', [
            'account_name' => 'Compte principal',
            'opening_balance' => -1,
            'expected_income' => 0,
            'next_income_on' => today()->subDay()->format('Y-m-d'),
            'commitments_before_income' => 0,
            'protected_savings' => 0,
            'safety_buffer' => 0,
            'essential_daily_target' => 3_000,
            'guard_mode' => 'unknown',
        ])->assertSessionHasErrors(['opening_balance', 'next_income_on', 'guard_mode']);

        $this->assertDatabaseCount(FinancialAccount::class, 0);
    }

    public function test_completed_users_are_sent_to_their_dashboard(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this->actingAs($user)
            ->get('/onboarding/financial')
            ->assertRedirect('/dashboard');
    }
}

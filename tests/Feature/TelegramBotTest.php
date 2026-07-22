<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialProfile;
use App\Models\SpendingProject;
use App\Models\TelegramConnection;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        FinancialProfile::query()->create(['user_id' => $user->id, 'essential_daily_target_amount' => 3_000]);
        FinancialAccount::query()->create(['user_id' => $user->id, 'name' => 'Principal', 'opening_balance_amount' => 500_000]);
        $connection = TelegramConnection::query()->create([
            'user_id' => $user->id, 'bot_token' => '123456:secret_token', 'chat_id' => '987654',
            'webhook_secret' => 'webhook-secret', 'is_active' => true,
        ]);
        return compact('user', 'connection');
    }

    private function message(TelegramConnection $connection, int $id, string $text): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'webhook-secret')
            ->postJson("/telegram/webhook/{$connection->id}", [
                'message' => ['message_id' => $id, 'date' => now()->timestamp, 'chat' => ['id' => 987654], 'text' => $text],
            ])->assertOk();
    }

    private function telegramCallback(TelegramConnection $connection, string $data, int $chatId = 987654): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'webhook-secret')
            ->postJson("/telegram/webhook/{$connection->id}", ['callback_query' => ['message' => ['chat' => ['id' => $chatId]], 'data' => $data]])
            ->assertOk();
    }

    public function test_bot_creates_assesses_and_reserves_a_project_through_the_guided_flow(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        ['connection' => $connection] = $this->context();

        $this->message($connection, 1, '/projet Sortie cinéma');
        $this->message($connection, 2, '/budget 40000');
        $this->message($connection, 3, '/date '.today()->addDays(10)->format('d/m/Y'));

        $project = SpendingProject::query()->firstOrFail();
        $this->assertSame('telegram', $project->source);
        $this->assertSame('approved', $project->latestAssessment->verdict);
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'webhook-secret')
            ->postJson("/telegram/webhook/{$connection->id}", ['callback_query' => ['message' => ['chat' => ['id' => 987654]], 'data' => "reserve:{$project->id}"]])
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['spending_project_id' => $project->id, 'amount' => 40_000, 'status' => 'active']);
        $this->assertDatabaseHas('telegram_messages', ['user_id' => $connection->user_id, 'direction' => 'incoming', 'text' => '/budget 40000']);
        Http::assertSentCount(4);
    }

    public function test_webhook_rejects_wrong_secret_and_wrong_chat(): void
    {
        Http::fake();
        ['connection' => $connection] = $this->context();
        $this->postJson("/telegram/webhook/{$connection->id}", [])->assertForbidden();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'webhook-secret')
            ->postJson("/telegram/webhook/{$connection->id}", ['message' => ['chat' => ['id' => 111], 'text' => '/aide']])
            ->assertOk();
        $this->assertDatabaseCount('telegram_messages', 0);
        Http::assertNothingSent();
    }

    public function test_duplicate_telegram_delivery_is_ignored(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        ['connection' => $connection] = $this->context();
        $this->message($connection, 42, '/projet Voyage');
        $this->message($connection, 42, '/projet Voyage');

        $this->assertDatabaseCount('telegram_messages', 2);
        Http::assertSentCount(1);
    }

    public function test_expense_is_recorded_with_category_and_nature_buttons_and_updates_balance(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        ['user' => $user, 'connection' => $connection] = $this->context();
        $this->message($connection, 50, '/depense 2500 Déjeuner');
        $category = ExpenseCategory::query()->where('user_id', $user->id)->where('name', 'Alimentation')->firstOrFail();
        $this->telegramCallback($connection, "expense-category:{$category->id}");
        $this->telegramCallback($connection, 'expense-nature:impulsive');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id, 'amount' => 2_500, 'description' => 'Déjeuner',
            'purchase_nature' => 'impulsive', 'source' => 'telegram',
        ]);
        $this->actingAs($user)->get('/expenses')->assertInertia(fn ($page) => $page->where('position.spendable', 497_500));
        Http::assertSentCount(3);
    }

    public function test_callback_from_another_chat_cannot_create_an_expense(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        ['user' => $user, 'connection' => $connection] = $this->context();
        $this->message($connection, 60, '/depense 1000 Test');
        $category = ExpenseCategory::query()->where('user_id', $user->id)->firstOrFail();
        $this->telegramCallback($connection, "expense-category:{$category->id}", 111111);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_daily_summary_is_sent_only_once_per_day(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        ['connection' => $connection] = $this->context();
        $connection->update(['verified_at' => now(), 'notification_preferences' => ['daily_summary' => true]]);

        $this->artisan('money-minder:send-daily-summaries')->assertSuccessful();
        $this->artisan('money-minder:send-daily-summaries')->assertSuccessful();

        $this->assertDatabaseHas('telegram_messages', ['telegram_connection_id' => $connection->id, 'kind' => 'daily_summary', 'external_message_id' => 'daily:'.today()->format('Y-m-d')]);
        Http::assertSentCount(1);
    }

    public function test_connection_token_is_encrypted_and_never_shared_with_the_page(): void
    {
        ['user' => $user] = $this->context();
        $this->assertDatabaseMissing('telegram_connections', ['bot_token' => '123456:secret_token']);
        $this->actingAs($user)->get('/telegram')->assertInertia(fn ($page) => $page
            ->component('Telegram/Index')->missing('connection.bot_token')->where('connection.chat_id', '987654'));
    }
}

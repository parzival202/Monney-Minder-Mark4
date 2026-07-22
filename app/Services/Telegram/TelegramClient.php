<?php

namespace App\Services\Telegram;

use App\Models\TelegramConnection;
use Illuminate\Support\Facades\Http;

final class TelegramClient
{
    public function send(TelegramConnection $connection, string $text, ?array $keyboard = null, string $kind = 'notification', ?string $externalId = null): void
    {
        $payload = ['chat_id' => $connection->chat_id, 'text' => $text];
        if ($keyboard) {
            $payload['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        Http::timeout(10)->post($this->url($connection, 'sendMessage'), $payload)->throw();

        $connection->messages()->create([
            'user_id' => $connection->user_id, 'chat_id' => $connection->chat_id,
            'external_message_id' => $externalId, 'direction' => 'outgoing', 'kind' => $kind, 'text' => $text,
            'payload' => $payload, 'sent_at' => now(),
        ]);
    }

    public function registerWebhook(TelegramConnection $connection): void
    {
        Http::timeout(10)->post($this->url($connection, 'setWebhook'), [
            'url' => route('telegram.webhook', $connection),
            'secret_token' => $connection->webhook_secret,
            'allowed_updates' => ['message', 'callback_query'],
        ])->throw();
    }

    private function url(TelegramConnection $connection, string $method): string
    {
        return "https://api.telegram.org/bot{$connection->bot_token}/{$method}";
    }
}

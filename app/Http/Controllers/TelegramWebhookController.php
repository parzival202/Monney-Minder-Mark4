<?php

namespace App\Http\Controllers;

use App\Models\TelegramConnection;
use App\Services\Telegram\TelegramDecisionBot;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, TelegramConnection $connection, TelegramDecisionBot $bot): Response
    {
        $secret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
        abort_unless($connection->is_active && $connection->webhook_secret && hash_equals($connection->webhook_secret, $secret), 403);
        $bot->handle($connection, $request->all());

        return response('OK');
    }
}

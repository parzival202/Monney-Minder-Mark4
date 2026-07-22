<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TelegramSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $connection = $request->user()->telegramConnection;
        return Inertia::render('Telegram/Index', [
            'connection' => $connection ? [
                'chat_id' => $connection->chat_id, 'telegram_username' => $connection->telegram_username,
                'is_active' => $connection->is_active, 'verified_at' => $connection->verified_at?->toIso8601String(),
                'last_update_at' => $connection->last_update_at?->toIso8601String(),
                'webhook_url' => route('telegram.webhook', $connection),
                'notification_preferences' => $connection->notification_preferences ?? [],
            ] : null,
            'messages' => $request->user()->telegramMessages()->latest('sent_at')->limit(30)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bot_token' => ['required', 'string', 'regex:/^\d+:[A-Za-z0-9_-]+$/'],
            'chat_id' => ['required', 'regex:/^-?\d+$/'],
            'telegram_username' => ['nullable', 'string', 'max:100'],
        ]);
        $existing = $request->user()->telegramConnection;
        $request->user()->telegramConnection()->updateOrCreate([], [
            ...$data, 'webhook_secret' => $existing?->webhook_secret ?: Str::random(48),
            'is_active' => true, 'notification_preferences' => ['decision_alerts' => true, 'daily_summary' => true],
        ]);
        return back()->with('success', 'Connexion Telegram enregistrée de manière chiffrée.');
    }

    public function activate(Request $request, TelegramClient $client): RedirectResponse
    {
        $connection = $request->user()->telegramConnection()->firstOrFail();
        $client->registerWebhook($connection);
        $connection->update(['verified_at' => now(), 'is_active' => true]);
        return back()->with('success', 'Bot activé. Envoie /aide dans Telegram.');
    }

    public function preferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['daily_summary' => ['required', 'boolean'], 'decision_alerts' => ['required', 'boolean']]);
        $request->user()->telegramConnection()->firstOrFail()->update(['notification_preferences' => $data]);
        return back()->with('success', 'Préférences Telegram mises à jour.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->telegramConnection?->delete();
        return back()->with('success', 'Connexion Telegram supprimée. Les messages importés restent dans ton historique.');
    }
}

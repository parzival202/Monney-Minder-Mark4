<?php

namespace App\Services\Telegram;

use App\Models\Reservation;
use App\Models\SpendingProject;
use App\Models\Transaction;
use App\Services\Expenses\DefaultExpenseCategories;
use App\Services\Finance\FinancialPositionService;
use App\Models\TelegramConnection;
use App\Services\Projects\ProjectAssessmentService;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class TelegramDecisionBot
{
    public function __construct(
        private TelegramClient $client,
        private ProjectAssessmentService $assessments,
        private DefaultExpenseCategories $defaultCategories,
        private FinancialPositionService $positions,
        private TelegramAlertService $alerts,
    ) {}

    public function handle(TelegramConnection $connection, array $update): void
    {
        $connection->update(['last_update_at' => now()]);
        if (isset($update['callback_query'])) {
            if ((string) data_get($update, 'callback_query.message.chat.id', '') !== $connection->chat_id) return;
            $this->callback($connection, (string) ($update['callback_query']['data'] ?? ''));
            return;
        }

        $message = $update['message'] ?? null;
        if (!$message || (string) ($message['chat']['id'] ?? '') !== $connection->chat_id) {
            return;
        }
        $text = trim((string) ($message['text'] ?? ''));
        $externalId = isset($message['message_id']) ? (string) $message['message_id'] : null;
        if ($externalId && $connection->messages()->where('external_message_id', $externalId)->exists()) {
            return;
        }
        $connection->messages()->create([
            'user_id' => $connection->user_id, 'external_message_id' => $externalId,
            'chat_id' => $connection->chat_id, 'direction' => 'incoming', 'text' => $text,
            'payload' => $message, 'sent_at' => isset($message['date']) ? Carbon::createFromTimestamp($message['date']) : now(),
        ]);
        $this->command($connection, $text);
    }

    private function command(TelegramConnection $connection, string $text): void
    {
        $user = $connection->user;
        $conversation = $user->telegramConversation()->firstOrCreate([], ['state' => 'idle', 'data' => []]);
        if (preg_match('/^\/projet\s+(.+?)\s+\/budget\s+([\d\s.,]+?)\s+\/date\s+(\d{1,2}\/\d{1,2}(?:\/\d{4})?)\s*$/iu', $text, $matches)) {
            $this->assessProject($connection, $conversation, trim($matches[1]), (int) preg_replace('/\D/', '', $matches[2]), $matches[3]);
            return;
        }
        if (preg_match('/^\/projet\s+(.+)$/iu', $text, $matches)) {
            $name = trim($matches[1]);
            $conversation->update(['state' => 'project', 'data' => ['name' => $name], 'last_interaction_at' => now()]);
            $this->client->send($connection, "Projet « {$name} » créé. Envoie /budget 40000.");
            return;
        }
        if (preg_match('/^\/budget(?:\S*)?\s+([\d\s.,]+)$/iu', $text, $matches)) {
            $data = $conversation->data ?? [];
            if (empty($data['name'])) { $this->client->send($connection, 'Commence par /projet Nom de la sortie.'); return; }
            $data['amount'] = (int) preg_replace('/\D/', '', $matches[1]);
            $conversation->update(['state' => 'project', 'data' => $data, 'last_interaction_at' => now()]);
            $this->client->send($connection, 'Budget enregistré. Envoie /date 27/07 ou /date 27/07/2026.');
            return;
        }
        if (preg_match('/^\/date\s+(\d{1,2}\/\d{1,2}(?:\/\d{4})?)$/iu', $text, $matches)) {
            $data = $conversation->data ?? [];
            if (empty($data['name']) || empty($data['amount'])) { $this->client->send($connection, 'Il manque le projet ou son budget. Utilise /projet puis /budget.'); return; }
            $this->assessProject($connection, $conversation, $data['name'], (int) $data['amount'], $matches[1]);
            return;
        }
        if (preg_match('/^\/depense\s+([\d\s.,]+)\s+(.+)$/iu', $text, $matches)) {
            $amount = (int) preg_replace('/\D/', '', $matches[1]);
            if ($amount < 1) { $this->client->send($connection, 'Le montant doit être supérieur à zéro.'); return; }
            $this->defaultCategories->ensureFor($user);
            $conversation->update(['state' => 'expense_category', 'data' => ['amount' => $amount, 'description' => trim($matches[2])], 'last_interaction_at' => now()]);
            $buttons = $user->expenseCategories()->where('is_active', true)->orderBy('name')->get()
                ->map(fn ($category) => [['text' => $category->name, 'callback_data' => "expense-category:{$category->id}"]])->all();
            $this->client->send($connection, 'Dans quelle catégorie classer cette dépense ?', $buttons);
            return;
        }
        if ($text === '/solde') {
            $position = $this->positions->forUser($user);
            $this->client->send($connection, "Disponible réel : {$this->money($position['spendable'])}\nPar jour : {$this->money($position['daily_available'])}\nRéservé aux projets : {$this->money($position['reservations'])}");
            return;
        }
        if ($text === '/projets') {
            $projects = $user->spendingProjects()->with('activeReservation')->whereIn('status', ['assessed', 'saving', 'reserved'])->orderBy('target_date')->limit(10)->get();
            $lines = $projects->map(fn ($project) => "• {$project->name} — {$this->money($project->target_amount)} — ".$project->target_date->format('d/m/Y'));
            $this->client->send($connection, $lines->isEmpty() ? 'Aucun projet actif.' : "Tes projets :\n".$lines->implode("\n"));
            return;
        }
        if ($text === '/annuler') {
            $conversation->update(['state' => 'idle', 'data' => [], 'last_interaction_at' => now()]);
            $this->client->send($connection, 'Action en cours annulée.');
            return;
        }
        if (in_array(Str::lower($text), ['/start', '/aide', '/help'], true)) {
            $this->client->send($connection, "Je peux décider et enregistrer avec toi.\n\nSimulation en un message :\n/projet Cinéma /budget 40000 /date 27/07\n\nOu étape par étape :\n1. /projet Cinéma\n2. /budget 40000\n3. /date 27/07\n\nDépense réelle :\n/depense 2500 Déjeuner\n\nConsultation : /solde · /projets\nAnnuler : /annuler");
            return;
        }
        $this->client->send($connection, 'Commande inconnue. Envoie /aide pour voir le parcours de décision.');
    }

    private function assessProject(TelegramConnection $connection, $conversation, string $name, int $amount, string $dateValue): void
    {
        if ($name === '' || $amount < 1) { $this->client->send($connection, 'Le nom et le budget du projet sont obligatoires.'); return; }
        $date = $this->date($dateValue);
        if (!$date || $date->isBefore(today())) { $this->client->send($connection, 'Date invalide ou déjà passée. Exemple : /date 27/07/2027.'); return; }
        $project = $connection->user->spendingProjects()->create([
            'name' => $name, 'target_amount' => $amount, 'target_date' => $date,
            'priority' => 'want', 'status' => 'assessed', 'source' => 'telegram',
        ]);
        $assessment = $this->assessments->assess($connection->user, $project, 'telegram');
        $conversation->update(['state' => 'idle', 'data' => [], 'last_interaction_at' => now()]);
        $message = $this->verdict($assessment->verdict).' '.Str::of($assessment->summary)->replaceMatches('/(-?\d+)/', fn ($m) => number_format((int) $m[1], 0, ',', ' ').' FCFA');
        $keyboard = in_array($assessment->verdict, ['approved', 'caution'], true)
            ? [[['text' => '✅ Réserver la somme', 'callback_data' => "reserve:{$project->id}"], ['text' => 'Pas maintenant', 'callback_data' => "dismiss:{$project->id}"]]] : null;
        $this->client->send($connection, $message, $keyboard);
    }

    private function callback(TelegramConnection $connection, string $data): void
    {
        if (preg_match('/^expense-category:(\d+)$/', $data, $matches)) {
            $conversation = $connection->user->telegramConversation;
            $category = $connection->user->expenseCategories()->where('is_active', true)->find($matches[1]);
            if (!$conversation || $conversation->state !== 'expense_category' || !$category) return;
            $values = [...($conversation->data ?? []), 'expense_category_id' => $category->id];
            $conversation->update(['state' => 'expense_nature', 'data' => $values, 'last_interaction_at' => now()]);
            $this->client->send($connection, 'Cette dépense était-elle prévue ?', [[
                ['text' => '✅ Prévue', 'callback_data' => 'expense-nature:planned'],
                ['text' => '🧯 Nécessaire imprévue', 'callback_data' => 'expense-nature:unplanned_necessary'],
            ], [['text' => '⚡ Impulsive', 'callback_data' => 'expense-nature:impulsive']]]);
            return;
        }
        if (preg_match('/^expense-nature:(planned|unplanned_necessary|impulsive)$/', $data, $matches)) {
            $conversation = $connection->user->telegramConversation;
            $values = $conversation?->data ?? [];
            $category = $connection->user->expenseCategories()->find($values['expense_category_id'] ?? 0);
            $account = $connection->user->financialAccounts()->where('is_active', true)->orderByDesc('included_in_planning')->first();
            if (!$conversation || $conversation->state !== 'expense_nature' || !$category || !$account) return;
            $expense = Transaction::query()->create([
                'user_id' => $connection->user_id, 'financial_account_id' => $account->id,
                'expense_category_id' => $category->id, 'type' => 'expense', 'category' => $category->name,
                'description' => $values['description'], 'amount' => $values['amount'], 'occurred_on' => today(),
                'purchase_nature' => $matches[1], 'is_essential' => $category->is_essential || $matches[1] === 'unplanned_necessary',
                'source' => 'telegram', 'metadata' => ['telegram' => true],
            ]);
            $conversation->update(['state' => 'idle', 'data' => [], 'last_interaction_at' => now()]);
            $position = $this->positions->forUser($connection->user);
            $warning = $position['daily_available'] < $position['essential_daily_target'] ? "\n⚠️ Tu es maintenant sous ton besoin quotidien recommandé." : '';
            $this->client->send($connection, "✅ Dépense de {$this->money($values['amount'])} enregistrée.\nDisponible : {$this->money($position['spendable'])}\nPar jour : {$this->money($position['daily_available'])}{$warning}");
            $this->alerts->afterExpense($connection->user, $expense);
            return;
        }
        if (!preg_match('/^(reserve|dismiss):(\d+)$/', $data, $matches)) return;
        $project = SpendingProject::query()->where('user_id', $connection->user_id)->find($matches[2]);
        if (!$project) return;
        if ($matches[1] === 'dismiss') { $this->client->send($connection, 'Projet conservé sans réserver la somme.'); return; }
        if ($project->activeReservation()->exists()) { $this->client->send($connection, 'Cette somme est déjà réservée.'); return; }
        $assessment = $project->latestAssessment()->first();
        if (!$assessment || !in_array($assessment->verdict, ['approved', 'caution'], true)) { $this->client->send($connection, 'La situation a changé : recalcule le projet avant de réserver.'); return; }
        Reservation::query()->create([
            'user_id' => $connection->user_id, 'spending_project_id' => $project->id,
            'amount' => $project->target_amount, 'target_amount' => $project->target_amount,
            'strategy' => 'immediate', 'status' => 'active', 'reserved_on' => today(),
        ]);
        $project->update(['status' => 'reserved']);
        $this->client->send($connection, '✅ Somme réservée. Elle est maintenant retirée de ton disponible quotidien.');
    }

    private function date(string $value): ?Carbon
    {
        try {
            $format = substr_count($value, '/') === 1 ? 'd/m/Y' : 'd/m/Y';
            $withoutYear = substr_count($value, '/') === 1;
            if ($withoutYear) $value .= '/'.today()->year;
            $date = Carbon::createFromFormat('!'.$format, $value);
            return $withoutYear && $date->isBefore(today()) ? $date->addYear() : $date;
        } catch (\Throwable) { return null; }
    }

    private function verdict(string $verdict): string
    {
        return match ($verdict) { 'approved' => '✅ Sortie approuvée.', 'caution' => '🟠 Sortie possible avec prudence.', 'risky' => '⚠️ Sortie risquée.', default => '⛔ Sortie impossible.' };
    }

    private function money(int $amount): string { return number_format($amount, 0, ',', ' ').' FCFA'; }
}

<?php

namespace App\Services\Telegram;

use App\Models\TelegramConnection;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Finance\BudgetCycleService;

final class TelegramAlertService
{
    private const MESSAGES = [
        'large_expense' => [
            "😳 {amount} pour {category} ? C’est une grosse dépense. On vérifie que le reste du cycle tient toujours ?",
            "🚨 Nikolaii à l’appareil : {amount} dépensés en {category}. Ton futur toi mérite qu’on regarde l’impact.",
            "💸 {amount} d’un coup pour {category}. Respire : il reste {remaining} pour terminer le cycle.",
        ],
        'impulsive' => [
            "⚡ Achat impulsif enregistré : {amount}. Aucun jugement, mais on le compte franchement pour garder le contrôle.",
            "👀 {amount} classés en impulsif. La prochaine fois, on tente la pause de 10 minutes avant de payer ?",
            "Nikolaii note l’impulsion : {amount}. Il reste {remaining} sur ton budget de cycle.",
        ],
        'daily_warning' => [
            "⚠️ Tu as déjà dépensé {today} aujourd’hui, soit {percentage}% de ton rythme quotidien conseillé.",
            "Psst… {today} aujourd’hui. Tu approches de ta limite de {daily_target} : on calme le jeu ?",
        ],
        'daily_limit' => [
            "🚨 Limite quotidienne dépassée : {today} dépensés pour un rythme conseillé de {daily_target}.",
            "Tchai 😅 {today} aujourd’hui ! Pour protéger la fin du cycle, les dépenses non essentielles peuvent attendre.",
            "Code rouge du jour : {today}. Disponible quotidien conseillé : {daily_target}.",
        ],
        'cycle_warning' => [
            "🟠 Ton budget du cycle est consommé à {percentage}%. Il reste {remaining} jusqu’au {cycle_end}.",
            "Le cycle avance vite : {percentage}% du budget est déjà parti. Marge restante : {remaining}.",
        ],
        'cycle_exceeded' => [
            "🚨 Budget du cycle épuisé. Les prochaines dépenses doivent être strictement essentielles.",
            "Nikolaii ne va pas mentir : le budget du cycle est dépassé de {over}. On passe en mode protection.",
        ],
    ];

    public function __construct(private TelegramClient $client, private BudgetCycleService $cycles) {}

    public function afterExpense(User $user, Transaction $expense): void
    {
        $connection = $user->telegramConnection()->where('is_active', true)->whereNotNull('verified_at')->first();
        if (!$connection || !(bool) data_get($connection->notification_preferences, 'decision_alerts', true)) return;

        $cycle = $this->cycles->forUser($user);
        $cycleSpent = (int) $user->transactions()->where('type', 'expense')->whereBetween('occurred_on', [$cycle['start'], $cycle['end']])->sum('amount');
        $todaySpent = (int) $user->transactions()->where('type', 'expense')->whereDate('occurred_on', today())->sum('amount');
        $remaining = max($cycle['budget'] - $cycleSpent, 0);
        $dailyTarget = max($cycle['budget'] > 0 ? intdiv($cycle['budget'], $cycle['days_total']) : 0, (int) $user->financialProfile->essential_daily_target_amount, 1);
        $dailyPercentage = (int) round($todaySpent / $dailyTarget * 100);
        $cyclePercentage = $cycle['budget'] > 0 ? (int) round($cycleSpent / $cycle['budget'] * 100) : 0;
        $vars = [
            'amount' => $this->money((int) $expense->amount), 'category' => $expense->category,
            'remaining' => $this->money($remaining), 'today' => $this->money($todaySpent),
            'daily_target' => $this->money($dailyTarget), 'percentage' => 0,
            'cycle_end' => $cycle['end']->format('d/m'), 'over' => $this->money(max($cycleSpent - $cycle['budget'], 0)),
        ];

        if ($expense->purchase_nature === 'impulsive') $this->sendOnce($connection, 'impulsive', "expense:{$expense->id}:impulsive", $vars);
        if ((int) $expense->amount >= max($dailyTarget * 2, 10_000)) $this->sendOnce($connection, 'large_expense', "expense:{$expense->id}:large", $vars);
        if ($dailyPercentage >= 100) $this->sendOnce($connection, 'daily_limit', 'daily-limit:'.today()->format('Y-m-d'), [...$vars, 'percentage' => $dailyPercentage]);
        elseif ($dailyPercentage >= 80) $this->sendOnce($connection, 'daily_warning', 'daily-warning:'.today()->format('Y-m-d'), [...$vars, 'percentage' => $dailyPercentage]);
        if ($cycle['budget'] > 0 && $cyclePercentage >= 100) $this->sendOnce($connection, 'cycle_exceeded', 'cycle-exceeded:'.$cycle['start']->format('Y-m-d'), [...$vars, 'percentage' => $cyclePercentage]);
        elseif ($cyclePercentage >= 80) $this->sendOnce($connection, 'cycle_warning', 'cycle-warning:'.$cycle['start']->format('Y-m-d'), [...$vars, 'percentage' => $cyclePercentage]);
    }

    private function sendOnce(TelegramConnection $connection, string $type, string $key, array $vars): void
    {
        if ($connection->messages()->where('external_message_id', $key)->exists()) return;
        $templates = self::MESSAGES[$type];
        $text = $templates[array_rand($templates)];
        foreach ($vars as $name => $value) $text = str_replace('{'.$name.'}', (string) $value, $text);
        try { $this->client->send($connection, $text, null, 'behavior_alert', $key); }
        catch (\Throwable $exception) { report($exception); }
    }

    private function money(int $amount): string { return number_format(max($amount, 0), 0, ',', ' ').' FCFA'; }
}

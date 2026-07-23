<?php

namespace App\Services\Telegram;

use App\Models\TelegramConnection;
use App\Services\Finance\BudgetCycleService;

final class TelegramCompanionService
{
    public function __construct(private TelegramClient $client, private BudgetCycleService $cycles) {}

    public function sendIfUseful(TelegramConnection $connection, bool $force = false): bool
    {
        if (!(bool) data_get($connection->notification_preferences, 'ambient_nudges', true)) return false;
        $user = $connection->user;
        $timezone = $user->financialProfile?->timezone ?: config('app.timezone');
        $localNow = now($timezone);
        if (!$force && ($localNow->hour < 9 || $localNow->hour >= 21)) return false;

        $todayMessages = $connection->messages()->where('kind', 'ambient_nudge')->whereDate('sent_at', $localNow->toDateString())->count();
        if ($todayMessages >= 2) return false;
        if (!$force && $connection->messages()->where('kind', 'ambient_nudge')->where('sent_at', '>', now()->subHours(5))->exists()) return false;
        if (!$force && random_int(1, 100) > 22) return false;

        $cycle = $this->cycles->forUser($user);
        $spent = (int) $user->transactions()->where('type', 'expense')->whereBetween('occurred_on', [$cycle['start'], $cycle['end']])->sum('amount');
        $todaySpent = (int) $user->transactions()->where('type', 'expense')->whereDate('occurred_on', $localNow->toDateString())->sum('amount');
        $used = $cycle['budget'] > 0 ? (int) round($spent / $cycle['budget'] * 100) : 0;
        $expected = (int) round($cycle['days_elapsed'] / $cycle['days_total'] * 100);
        $project = $user->spendingProjects()->whereIn('status', ['assessed', 'saving', 'reserved'])->whereBetween('target_date', [today(), today()->addDays(10)])->inRandomOrder()->first();

        if ($project && random_int(0, 1)) {
            $text = $this->pick([
                "📅 Petit rappel : « {$project->name} » approche. Tu veux vérifier si son budget est toujours réaliste ?",
                "Nikolaii pense à ton projet « {$project->name} ». On garde le cap jusqu’au ".$project->target_date->format('d/m').' ?',
            ]);
        } elseif ($localNow->hour >= 18 && $todaySpent === 0) {
            $text = $this->pick([
                "👀 Rien enregistré aujourd’hui. Journée sans dépense ou petite dépense oubliée ?",
                "Alors chef, journée économe aujourd’hui ? Si tu as payé quelque chose, je suis prêt : /depense montant description",
                "Silence radio côté dépenses aujourd’hui 😌. C’est une victoire ou on a oublié un ticket ?",
            ]);
        } elseif ($cycle['budget'] > 0 && $used > $expected + 12) {
            $text = $this->pick([
                "🟠 Petit point spontané : {$used}% du budget est consommé alors que le cycle est avancé à {$expected}%. On ralentit un peu ?",
                "Nikolaii surveille le rythme : les dépenses vont plus vite que le cycle. Une journée calme ferait du bien au budget.",
            ]);
        } elseif ($cycle['budget'] > 0 && $used + 12 < $expected) {
            $text = $this->pick([
                "🎯 Tu dépenses moins vite que prévu ce cycle. Continue comme ça, la marge se construit toute seule.",
                "Petit passage de Nikolaii pour dire bravo : ton rythme est propre aujourd’hui 👏.",
                "Côté Agni activé 🕺 : budget bien tenu pour le moment. Garde cette énergie !",
            ]);
        } else {
            $text = $this->pick([
                "Question rapide : la prochaine dépense prévue aujourd’hui est-elle vraiment prioritaire ? 🤔",
                "Nikolaii passe juste vérifier : ton argent travaille pour toi, ou tu travailles encore pour tes impulsions ? 😄",
                "Petit rappel amical : consulter /solde avant un achat évite beaucoup de regrets.",
                "Tu as un achat en tête ? Donne-moi le projet et le budget, on regarde sa viabilité ensemble.",
            ]);
        }

        $key = 'ambient:'.$localNow->format('Y-m-d').':'.($todayMessages + 1);
        try {
            $this->client->send($connection, $text, null, 'ambient_nudge', $key);
            return true;
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function pick(array $messages): string { return $messages[array_rand($messages)]; }
}

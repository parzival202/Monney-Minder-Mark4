<?php

namespace App\Console\Commands;

use App\Models\TelegramConnection;
use App\Services\Finance\FinancialPositionService;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;

class SendDailyTelegramSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'money-minder:send-daily-summaries {--user= : Limiter à un ID ou un e-mail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie le résumé financier quotidien et les rappels de projets dans Telegram';

    /**
     * Execute the console command.
     */
    public function handle(TelegramClient $client, FinancialPositionService $positions): int
    {
        $query = TelegramConnection::query()->with('user.financialProfile')->where('is_active', true)->whereNotNull('verified_at');
        if ($identifier = $this->option('user')) {
            $query->whereHas('user', fn ($q) => $q->where('id', $identifier)->orWhere('email', $identifier));
        }
        $sent = 0;
        foreach ($query->get() as $connection) {
            if (!(bool) data_get($connection->notification_preferences, 'daily_summary', true)) continue;
            $key = 'daily:'.today()->format('Y-m-d');
            if ($connection->messages()->where('external_message_id', $key)->exists()) continue;
            try {
                $position = $positions->forUser($connection->user);
                $spentToday = (int) $connection->user->transactions()->where('type', 'expense')->whereDate('occurred_on', today())->sum('amount');
                $dueReservations = $connection->user->reservations()->with('spendingProject')->where('status', 'active')->whereDate('next_contribution_on', '<=', today())->get();
                $upcoming = $connection->user->spendingProjects()->whereIn('status', ['assessed', 'saving', 'reserved'])->whereBetween('target_date', [today(), today()->addDays(7)])->orderBy('target_date')->get();
                $lines = [
                    '☀️ Ton point MoneyMinder',
                    '',
                    'Disponible réel : '.$this->money($position['spendable']),
                    'Disponible par jour : '.$this->money($position['daily_available']),
                    'Dépensé aujourd’hui : '.$this->money($spentToday),
                    'Réservé aux projets : '.$this->money($position['reservations']),
                ];
                if ($position['daily_available'] < $position['essential_daily_target']) $lines[] = '⚠️ Ton disponible quotidien est sous ton besoin essentiel.';
                foreach ($dueReservations as $reservation) $lines[] = '💰 Versement prévu pour « '.$reservation->spendingProject->name.' ».';
                foreach ($upcoming as $project) $lines[] = '📅 « '.$project->name.' » arrive le '.$project->target_date->format('d/m').'.';
                $client->send($connection, implode("\n", $lines), null, 'daily_summary', $key);
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn("Échec pour la connexion {$connection->id}.");
            }
        }
        $this->info("{$sent} résumé(s) envoyé(s).");
        return self::SUCCESS;
    }

    private function money(int $amount): string { return number_format($amount, 0, ',', ' ').' FCFA'; }
}

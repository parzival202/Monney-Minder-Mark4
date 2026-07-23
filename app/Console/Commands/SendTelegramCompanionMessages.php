<?php

namespace App\Console\Commands;

use App\Models\TelegramConnection;
use App\Services\Telegram\TelegramCompanionService;
use Illuminate\Console\Command;

class SendTelegramCompanionMessages extends Command
{
    protected $signature = 'money-minder:telegram-companion {--user= : ID ou e-mail} {--force : Ignore le tirage aléatoire et les heures calmes}';
    protected $description = 'Donne vie à Nikolaii avec des messages financiers contextuels et non intrusifs';

    public function handle(TelegramCompanionService $companion): int
    {
        $query = TelegramConnection::query()->with(['user.financialProfile'])->where('is_active', true)->whereNotNull('verified_at');
        if ($identifier = $this->option('user')) $query->whereHas('user', fn ($q) => $q->where('id', $identifier)->orWhere('email', $identifier));
        $sent = 0;
        foreach ($query->get() as $connection) if ($companion->sendIfUseful($connection, (bool) $this->option('force'))) $sent++;
        $this->info("{$sent} message(s) spontané(s) envoyé(s).");
        return self::SUCCESS;
    }
}

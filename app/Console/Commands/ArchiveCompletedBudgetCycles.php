<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Finance\BudgetCycleArchiveService;
use Illuminate\Console\Command;

class ArchiveCompletedBudgetCycles extends Command
{
    protected $signature = 'money-minder:archive-cycles';
    protected $description = 'Archive le dernier cycle terminé pour les utilisateurs ayant activé cette option';

    public function handle(BudgetCycleArchiveService $archives): int
    {
        User::query()
            ->whereHas('financialProfile', fn ($query) => $query->where('auto_archive_cycles', true))
            ->with('financialProfile')
            ->each(function (User $user) use ($archives): void {
                $archives->archivePreviousCycle($user);
            });

        $this->info('Cycles terminés archivés.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTelegramExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'money-minder:import-telegram-export {file : Chemin vers result.json} {--user= : ID ou e-mail du propriétaire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importe les messages et notifications d’un export JSON Telegram Mark 3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = realpath((string) $this->argument('file'));
        if (!$file || !is_file($file)) { $this->error('Fichier introuvable.'); return self::FAILURE; }
        $identifier = $this->option('user');
        $user = $identifier
            ? User::query()->where('id', $identifier)->orWhere('email', $identifier)->first()
            : (User::query()->count() === 1 ? User::query()->first() : null);
        if (!$user) { $this->error('Précise --user avec l’ID ou l’e-mail du propriétaire.'); return self::FAILURE; }
        $export = json_decode(file_get_contents($file), true);
        if (!is_array($export) || !isset($export['messages']) || !is_array($export['messages'])) { $this->error('Export Telegram JSON invalide.'); return self::FAILURE; }

        $count = DB::transaction(function () use ($user, $export): int {
            $count = 0;
            foreach ($export['messages'] as $message) {
                if (($message['type'] ?? 'message') !== 'message') continue;
                $text = $this->text($message['text'] ?? '');
                $user->telegramMessages()->updateOrCreate(
                    ['telegram_connection_id' => null, 'external_message_id' => 'import:'.($message['id'] ?? sha1(json_encode($message)))],
                    ['direction' => ($message['from'] ?? '') === ($export['name'] ?? '') ? 'outgoing' : 'incoming',
                     'kind' => str_contains(mb_strtolower($text), 'notification') ? 'notification' : 'message',
                     'text' => $text, 'payload' => $message, 'imported' => true,
                     'sent_at' => isset($message['date']) ? Carbon::parse($message['date']) : null]
                );
                $count++;
            }
            return $count;
        });
        $this->info("{$count} messages et notifications importés pour {$user->name}.");
        return self::SUCCESS;
    }

    private function text(mixed $value): string
    {
        if (is_string($value)) return $value;
        if (!is_array($value)) return '';
        return collect($value)->map(fn ($part) => is_string($part) ? $part : ($part['text'] ?? ''))->implode('');
    }
}

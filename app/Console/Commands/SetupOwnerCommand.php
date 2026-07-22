<?php

namespace App\Console\Commands;

use App\Models\FinancialProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SetupOwnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'money-minder:setup-owner {--email= : Adresse e-mail du propriétaire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée de manière sécurisée le premier compte propriétaire Franck Olivier';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (User::query()->exists()) {
            $this->error('Un compte existe déjà. Cette commande ne peut être utilisée que sur une installation vide.');

            return self::FAILURE;
        }

        $name = (string) $this->ask('Nom complet', 'Franck Olivier');
        $email = (string) ($this->option('email') ?: $this->ask('Adresse e-mail'));
        $password = (string) $this->secret('Mot de passe (12 caractères minimum)');
        $confirmation = (string) $this->secret('Confirmez le mot de passe');

        $validator = Validator::make(compact('name', 'email', 'password', 'confirmation'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:12', 'same:confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            FinancialProfile::query()->create([
                'user_id' => $user->id,
                'currency' => 'XOF',
                'timezone' => 'Africa/Abidjan',
                'guard_mode' => 'balanced',
            ]);
        });

        $this->info('Le compte propriétaire a été créé. L’onboarding financier se poursuivra dans l’application.');

        return self::SUCCESS;
    }
}

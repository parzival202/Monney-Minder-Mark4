<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('users')->orderBy('id')->each(function ($user) use ($now): void {
            $exists = DB::table('financial_accounts')
                ->where('user_id', $user->id)
                ->whereRaw('LOWER(name) = ?', ['orange money'])
                ->exists();

            if (!$exists) {
                DB::table('financial_accounts')->insert([
                    'user_id' => $user->id,
                    'name' => 'Orange Money',
                    'type' => 'mobile_money',
                    'opening_balance_amount' => 0,
                    'opened_on' => today()->toDateString(),
                    'included_in_planning' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Les comptes sont des données utilisateur : un rollback ne doit jamais les supprimer.
    }
};

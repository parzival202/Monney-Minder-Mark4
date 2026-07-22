<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('financial_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('monthly_budget_amount')->default(0)->after('cycle_start_day');
            $table->boolean('cycle_budget_renews_automatically')->default(true)->after('monthly_budget_amount');
        });
        DB::table('financial_profiles')->where('cycle_start_day', 1)->update(['cycle_start_day' => 27]);
    }

    public function down(): void
    {
        Schema::table('financial_profiles', function (Blueprint $table) {
            $table->dropColumn(['monthly_budget_amount', 'cycle_budget_renews_automatically']);
        });
    }
};

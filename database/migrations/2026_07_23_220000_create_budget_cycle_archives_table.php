<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_cycle_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('cycle_start');
            $table->date('cycle_end');
            $table->unsignedBigInteger('budget_amount')->default(0);
            $table->unsignedBigInteger('total_spent_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount')->default(0);
            $table->unsignedBigInteger('overspent_amount')->default(0);
            $table->unsignedBigInteger('impulsive_amount')->default(0);
            $table->unsignedBigInteger('essential_amount')->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->unsignedSmallInteger('no_spend_days')->default(0);
            $table->json('categories')->nullable();
            $table->json('projects')->nullable();
            $table->json('transactions')->nullable();
            $table->boolean('archived_automatically')->default(false);
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->unique(['user_id', 'cycle_start', 'cycle_end']);
        });

        Schema::table('financial_profiles', function (Blueprint $table) {
            $table->boolean('auto_archive_cycles')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('financial_profiles', function (Blueprint $table) {
            $table->dropColumn('auto_archive_cycles');
        });
        Schema::dropIfExists('budget_cycle_archives');
    }
};

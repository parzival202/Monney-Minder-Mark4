<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('XOF');
            $table->string('timezone')->default('Africa/Abidjan');
            $table->unsignedTinyInteger('payday_day')->nullable();
            $table->unsignedTinyInteger('cycle_start_day')->default(27);
            $table->unsignedBigInteger('protected_savings_amount')->default(0);
            $table->unsignedBigInteger('safety_buffer_amount')->default(0);
            $table->unsignedBigInteger('essential_daily_target_amount')->default(0);
            $table->string('guard_mode')->default('balanced');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_profiles');
    }
};

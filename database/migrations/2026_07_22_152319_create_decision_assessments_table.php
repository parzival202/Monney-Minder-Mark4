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
        Schema::create('decision_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spending_project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('requested_amount');
            $table->date('target_date');
            $table->string('verdict');
            $table->unsignedBigInteger('available_before_amount');
            $table->bigInteger('available_after_amount');
            $table->bigInteger('daily_after_amount');
            $table->unsignedBigInteger('recommended_daily_amount');
            $table->unsignedBigInteger('recommended_max_amount')->nullable();
            $table->unsignedInteger('days_to_cover');
            $table->text('summary');
            $table->json('calculation_snapshot');
            $table->string('source')->default('web');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_assessments');
    }
};

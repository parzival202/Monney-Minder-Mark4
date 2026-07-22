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
        Schema::create('planned_cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->string('label');
            $table->unsignedBigInteger('amount');
            $table->date('due_on');
            $table->string('status')->default('planned');
            $table->boolean('is_essential')->default(false);
            $table->string('source')->default('web');
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_cash_flows');
    }
};

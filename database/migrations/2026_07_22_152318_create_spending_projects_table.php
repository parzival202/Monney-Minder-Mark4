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
        Schema::create('spending_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('target_amount');
            $table->date('target_date');
            $table->string('priority')->default('want');
            $table->string('status')->default('draft');
            $table->string('source')->default('web');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'target_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spending_projects');
    }
};

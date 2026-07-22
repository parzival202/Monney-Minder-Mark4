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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_message_id')->nullable();
            $table->string('chat_id')->nullable();
            $table->string('direction')->default('incoming');
            $table->string('kind')->default('message');
            $table->text('text')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('imported')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['telegram_connection_id', 'external_message_id']);
            $table->index(['user_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};

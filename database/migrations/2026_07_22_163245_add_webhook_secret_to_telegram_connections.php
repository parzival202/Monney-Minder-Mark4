<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('telegram_connections', fn (Blueprint $table) => $table->string('webhook_secret', 64)->nullable()->after('chat_id'));
    }

    public function down(): void
    {
        Schema::table('telegram_connections', fn (Blueprint $table) => $table->dropColumn('webhook_secret'));
    }
};

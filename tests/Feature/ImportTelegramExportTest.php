<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportTelegramExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark3_json_messages_and_notifications_are_imported_without_duplicates(): void
    {
        $user = User::factory()->create();
        $path = storage_path('framework/testing/telegram-export.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'name' => 'MoneyMinder Bot',
            'messages' => [
                ['id' => 10, 'type' => 'message', 'date' => '2025-01-10T08:00:00', 'from' => 'Franck', 'text' => '/depense 2000 transport'],
                ['id' => 11, 'type' => 'message', 'date' => '2025-01-10T08:00:01', 'from' => 'MoneyMinder Bot', 'text' => ['Notification : ', ['type' => 'bold', 'text' => 'budget mis à jour']]],
            ],
        ], JSON_UNESCAPED_UNICODE));

        $this->artisan('money-minder:import-telegram-export', ['file' => $path, '--user' => $user->email])->assertSuccessful();
        $this->artisan('money-minder:import-telegram-export', ['file' => $path, '--user' => $user->email])->assertSuccessful();

        $this->assertDatabaseCount('telegram_messages', 2);
        $this->assertDatabaseHas('telegram_messages', ['user_id' => $user->id, 'external_message_id' => 'import:11', 'kind' => 'notification', 'imported' => true, 'text' => 'Notification : budget mis à jour']);
        File::delete($path);
    }
}

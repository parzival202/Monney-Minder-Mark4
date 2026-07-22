<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['payload' => 'array', 'imported' => 'boolean', 'sent_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function telegramConnection(): BelongsTo { return $this->belongsTo(TelegramConnection::class); }
}

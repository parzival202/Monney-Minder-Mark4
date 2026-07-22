<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramConversation extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['data' => 'array', 'last_interaction_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramConnection extends Model
{
    protected $guarded = [];

    protected $hidden = ['bot_token'];

    protected function casts(): array
    {
        return [
            'bot_token' => 'encrypted',
            'notification_preferences' => 'array',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'last_update_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function messages(): HasMany { return $this->hasMany(TelegramMessage::class); }
}

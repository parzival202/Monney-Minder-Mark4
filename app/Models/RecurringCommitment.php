<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringCommitment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['next_due_on' => 'date', 'is_essential' => 'boolean', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

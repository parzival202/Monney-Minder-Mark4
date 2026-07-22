<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannedCashFlow extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'is_essential' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

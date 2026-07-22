<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['reserved_on' => 'date', 'next_contribution_on' => 'date', 'released_on' => 'date']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function spendingProject(): BelongsTo { return $this->belongsTo(SpendingProject::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionAssessment extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['target_date' => 'date', 'calculation_snapshot' => 'array']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function spendingProject(): BelongsTo { return $this->belongsTo(SpendingProject::class); }
}

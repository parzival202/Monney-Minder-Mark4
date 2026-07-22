<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
            'cycle_budget_renews_automatically' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['occurred_on' => 'date', 'is_essential' => 'boolean', 'metadata' => 'array'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function financialAccount(): BelongsTo { return $this->belongsTo(FinancialAccount::class); }
    public function expenseCategory(): BelongsTo { return $this->belongsTo(ExpenseCategory::class); }
}

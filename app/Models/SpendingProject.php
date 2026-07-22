<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpendingProject extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['target_date' => 'date']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
    public function assessments(): HasMany { return $this->hasMany(DecisionAssessment::class); }
    public function latestAssessment(): HasOne { return $this->hasOne(DecisionAssessment::class)->latestOfMany(); }
    public function activeReservation(): HasOne { return $this->hasOne(Reservation::class)->where('status', 'active')->latestOfMany(); }
}

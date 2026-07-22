<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'onboarding_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function financialProfile(): HasOne { return $this->hasOne(FinancialProfile::class); }
    public function financialAccounts(): HasMany { return $this->hasMany(FinancialAccount::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function plannedCashFlows(): HasMany { return $this->hasMany(PlannedCashFlow::class); }
    public function spendingProjects(): HasMany { return $this->hasMany(SpendingProject::class); }
    public function reservations(): HasMany { return $this->hasMany(Reservation::class); }
    public function decisionAssessments(): HasMany { return $this->hasMany(DecisionAssessment::class); }
    public function expenseCategories(): HasMany { return $this->hasMany(ExpenseCategory::class); }
    public function telegramConnection(): HasOne { return $this->hasOne(TelegramConnection::class); }
    public function telegramMessages(): HasMany { return $this->hasMany(TelegramMessage::class); }
    public function telegramConversation(): HasOne { return $this->hasOne(TelegramConversation::class); }
}

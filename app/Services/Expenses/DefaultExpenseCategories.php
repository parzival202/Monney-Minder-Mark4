<?php

namespace App\Services\Expenses;

use App\Models\User;

final class DefaultExpenseCategories
{
    private const DEFAULTS = [
        ['name' => 'Alimentation', 'color' => '#10b981', 'is_essential' => true],
        ['name' => 'Transport', 'color' => '#0ea5e9', 'is_essential' => true],
        ['name' => 'Logement', 'color' => '#8b5cf6', 'is_essential' => true],
        ['name' => 'Santé', 'color' => '#ef4444', 'is_essential' => true],
        ['name' => 'Loisirs', 'color' => '#f59e0b', 'is_essential' => false],
        ['name' => 'Shopping', 'color' => '#ec4899', 'is_essential' => false],
        ['name' => 'Autre', 'color' => '#64748b', 'is_essential' => false],
    ];

    public function ensureFor(User $user): void
    {
        if ($user->expenseCategories()->exists()) {
            return;
        }

        foreach (self::DEFAULTS as $category) {
            $user->expenseCategories()->create($category);
        }
    }
}

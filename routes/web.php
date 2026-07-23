<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialOnboardingController;
use App\Http\Controllers\FinanceOverviewController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\PlannedCashFlowController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TelegramSettingsController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\BudgetCycleController;
use App\Http\Controllers\BudgetCycleArchiveController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/telegram/webhook/{connection}', TelegramWebhookController::class)->name('telegram.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding/financial', [FinancialOnboardingController::class, 'show'])
        ->name('onboarding.financial.show');
    Route::post('/onboarding/financial', [FinancialOnboardingController::class, 'store'])
        ->name('onboarding.financial.store');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/finances', FinanceOverviewController::class)->name('finances.index');
    Route::patch('/finances/budget-cycle', [BudgetCycleController::class, 'update'])->name('finances.budget-cycle.update');
    Route::get('/archives', [BudgetCycleArchiveController::class, 'index'])->name('archives.index');
    Route::post('/archives', [BudgetCycleArchiveController::class, 'store'])->name('archives.store');
    Route::patch('/archives/preferences', [BudgetCycleArchiveController::class, 'updatePreference'])->name('archives.preferences.update');
    Route::post('/finances/accounts', [FinancialAccountController::class, 'store'])->name('finances.accounts.store');
    Route::put('/finances/accounts/{financialAccount}', [FinancialAccountController::class, 'update'])->name('finances.accounts.update');
    Route::delete('/finances/accounts/{financialAccount}', [FinancialAccountController::class, 'destroy'])->name('finances.accounts.destroy');
    Route::post('/finances/cash-flows', [PlannedCashFlowController::class, 'store'])->name('finances.cash-flows.store');
    Route::put('/finances/cash-flows/{plannedCashFlow}', [PlannedCashFlowController::class, 'update'])->name('finances.cash-flows.update');
    Route::delete('/finances/cash-flows/{plannedCashFlow}', [PlannedCashFlowController::class, 'destroy'])->name('finances.cash-flows.destroy');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('/expenses/categories', [ExpenseCategoryController::class, 'store'])->name('expenses.categories.store');
    Route::put('/expenses/categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expenses.categories.update');
    Route::delete('/expenses/categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expenses.categories.destroy');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/reassess', [ProjectController::class, 'reassess'])->name('projects.reassess');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/reservations', [ReservationController::class, 'store'])->name('projects.reservations.store');
    Route::post('/reservations/{reservation}/contributions', [ReservationController::class, 'contribute'])->name('reservations.contribute');
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::get('/telegram', [TelegramSettingsController::class, 'index'])->name('telegram.index');
    Route::post('/telegram', [TelegramSettingsController::class, 'store'])->name('telegram.store');
    Route::post('/telegram/activate', [TelegramSettingsController::class, 'activate'])->name('telegram.activate');
    Route::patch('/telegram/preferences', [TelegramSettingsController::class, 'preferences'])->name('telegram.preferences');
    Route::delete('/telegram', [TelegramSettingsController::class, 'destroy'])->name('telegram.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

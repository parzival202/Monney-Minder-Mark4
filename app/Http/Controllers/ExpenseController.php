<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\ExpenseCategory;
use App\Models\Transaction;
use App\Services\Expenses\DefaultExpenseCategories;
use App\Services\Finance\FinancialPositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request, DefaultExpenseCategories $defaults, FinancialPositionService $positions): Response|RedirectResponse
    {
        $user = $request->user();
        if (!$user->onboarding_completed_at) {
            return redirect()->route('onboarding.financial.show');
        }

        $defaults->ensureFor($user);
        $expenses = $user->transactions()
            ->with(['financialAccount:id,name', 'expenseCategory:id,name,color'])
            ->where('type', 'expense')
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return Inertia::render('Expenses/Index', [
            'position' => $positions->forUser($user),
            'accounts' => $user->financialAccounts()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => $user->expenseCategories()->where('is_active', true)->orderBy('name')->get(),
            'expenses' => $expenses,
            'summary' => [
                'month_total' => (int) $user->transactions()->where('type', 'expense')->whereBetween('occurred_on', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
                'impulsive_total' => (int) $user->transactions()->where('type', 'expense')->where('purchase_nature', 'impulsive')->whereBetween('occurred_on', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->persist($request->user()->id, new Transaction(), $request->validated());

        return back()->with('success', 'Dépense enregistrée et disponible recalculé.');
    }

    public function update(UpdateExpenseRequest $request, Transaction $expense): RedirectResponse
    {
        $this->ensureExpenseOwner($request->user()->id, $expense);
        $this->persist($request->user()->id, $expense, $request->validated());

        return back()->with('success', 'Dépense mise à jour.');
    }

    public function destroy(Request $request, Transaction $expense): RedirectResponse
    {
        $this->ensureExpenseOwner($request->user()->id, $expense);
        $expense->delete();

        return back()->with('success', 'Dépense supprimée et disponible restauré.');
    }

    private function persist(int $userId, Transaction $expense, array $data): void
    {
        $category = ExpenseCategory::query()->where('user_id', $userId)->whereKey($data['expense_category_id'])->firstOrFail();
        $expense->fill([
            ...$data,
            'user_id' => $userId,
            'type' => 'expense',
            'category' => $category->name,
            'is_essential' => $category->is_essential || $data['purchase_nature'] === 'unplanned_necessary',
            'source' => 'web',
        ])->save();
    }

    private function ensureExpenseOwner(int $userId, Transaction $expense): void
    {
        abort_unless($expense->user_id === $userId && $expense->type === 'expense', 404);
    }
}

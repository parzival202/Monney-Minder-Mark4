<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $request->user()->expenseCategories()->create($request->validated());

        return back()->with('success', 'Catégorie ajoutée.');
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $expenseCategory);
        $expenseCategory->update($request->validated());

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $expenseCategory);

        if ($expenseCategory->transactions()->exists()) {
            $expenseCategory->update(['is_active' => false]);
        } else {
            $expenseCategory->delete();
        }

        return back()->with('success', 'Catégorie retirée. Les anciennes dépenses conservent leur libellé.');
    }

    private function ensureOwner(int $userId, ExpenseCategory $category): void
    {
        abort_unless($category->user_id === $userId, 404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialAccountRequest;
use App\Http\Requests\UpdateFinancialAccountRequest;
use App\Models\FinancialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancialAccountController extends Controller
{
    public function store(StoreFinancialAccountRequest $request): RedirectResponse
    {
        $request->user()->financialAccounts()->create([
            ...$request->validated(),
            'opened_on' => today(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Compte ajouté.');
    }

    public function update(UpdateFinancialAccountRequest $request, FinancialAccount $financialAccount): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $financialAccount);
        $financialAccount->update($request->validated());

        return back()->with('success', 'Compte mis à jour.');
    }

    public function destroy(Request $request, FinancialAccount $financialAccount): RedirectResponse
    {
        $this->ensureOwner($request->user()->id, $financialAccount);

        if ($financialAccount->transactions()->exists()) {
            return back()->withErrors(['account' => 'Ce compte contient des opérations et ne peut pas être supprimé.']);
        }

        if ($request->user()->financialAccounts()->count() <= 1) {
            return back()->withErrors(['account' => 'Au moins un compte financier doit être conservé.']);
        }

        $financialAccount->delete();

        return back()->with('success', 'Compte supprimé.');
    }

    private function ensureOwner(int $userId, FinancialAccount $account): void
    {
        abort_unless($account->user_id === $userId, 404);
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'quickExpense' => fn () => $request->user() && $request->user()->onboarding_completed_at ? [
                'accounts' => $request->user()->financialAccounts()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'categories' => $request->user()->expenseCategories()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']),
            ] : null,
        ];
    }
}

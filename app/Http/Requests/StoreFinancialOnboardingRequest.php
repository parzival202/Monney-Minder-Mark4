<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialOnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_name' => ['required', 'string', 'max:100'],
            'opening_balance' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'expected_income' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'next_income_on' => ['required', 'date', 'after_or_equal:today'],
            'commitments_before_income' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'protected_savings' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'safety_buffer' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'essential_daily_target' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'guard_mode' => ['required', 'in:flexible,balanced,strict'],
        ];
    }

    public function messages(): array
    {
        return [
            'next_income_on.after_or_equal' => 'La prochaine rentrée d’argent ne peut pas être dans le passé.',
            '*.integer' => 'Saisissez un montant entier en FCFA.',
            '*.min' => 'Le montant ne peut pas être négatif.',
        ];
    }
}

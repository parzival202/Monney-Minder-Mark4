<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
        $userId = $this->user()->id;

        return [
            'financial_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')->where('user_id', $userId)],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)->where('is_active', true))],
            'description' => ['required', 'string', 'max:180'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'purchase_nature' => ['required', 'in:planned,unplanned_necessary,impulsive'],
        ];
    }

    public function messages(): array
    {
        return [
            'occurred_on.before_or_equal' => 'Une dépense réelle ne peut pas être datée dans le futur.',
            'financial_account_id.exists' => 'Le compte sélectionné est invalide.',
            'expense_category_id.exists' => 'La catégorie sélectionnée est invalide.',
        ];
    }
}

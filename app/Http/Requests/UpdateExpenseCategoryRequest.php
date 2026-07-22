<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('expenseCategory');

        return $this->user() !== null
            && $category !== null
            && $category->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('expenseCategory');

        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('expense_categories', 'name')->where('user_id', $this->user()->id)->ignore($category?->id)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_essential' => ['required', 'boolean'],
        ];
    }
}

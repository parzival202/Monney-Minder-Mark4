<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlannedCashFlowRequest extends FormRequest
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
            'direction' => ['required', 'in:income,expense'],
            'label' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'due_on' => ['required', 'date'],
            'is_essential' => ['required', 'boolean'],
        ];
    }
}

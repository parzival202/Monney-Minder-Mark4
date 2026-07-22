<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user() !== null && $project !== null && $project->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'strategy' => ['required', 'in:immediate,progressive'],
            'initial_amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'contribution_frequency' => ['nullable', 'required_if:strategy,progressive', 'in:weekly,monthly'],
        ];
    }
}

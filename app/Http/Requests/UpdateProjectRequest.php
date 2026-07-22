<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'target_amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
            'priority' => ['required', 'in:need,important,want'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

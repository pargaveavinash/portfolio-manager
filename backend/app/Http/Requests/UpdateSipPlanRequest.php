<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSipPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'strategy' => ['sometimes', 'nullable', 'string', Rule::in(['optimization'])],
            'frequency' => ['sometimes', 'required', 'string', Rule::in(['daily', 'weekly', 'monthly', 'quarterly'])],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'paused', 'completed'])],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'timezone' => ['sometimes', 'nullable', 'string', 'timezone:all'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:ALLOCATION,PRICE_ABOVE,PRICE_BELOW,SIP_REMINDER',
            'reference_type' => 'required|string',
            'reference_id' => 'required',
            'threshold_value' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ];
    }
}

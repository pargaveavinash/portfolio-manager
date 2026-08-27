<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(['BUY', 'SELL']),
            ],
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'price' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'currency' => [
                'required',
                'string',
                'size:3',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
        ];
    }
}
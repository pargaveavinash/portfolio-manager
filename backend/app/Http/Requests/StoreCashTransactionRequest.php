<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashTransactionRequest extends FormRequest
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
                Rule::in(['DEPOSIT', 'WITHDRAWAL']),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
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

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
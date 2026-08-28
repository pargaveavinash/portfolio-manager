<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'             => [
                'required',
                Rule::in(['BUY', 'SELL']),
            ],

            'quantity'         => [
                'required',
                'numeric',
                'gt:0',
            ],

            'price'            => [
                'required',
                'numeric',
                'gte:0',
            ],

            'currency'         => [
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('type') !== 'SELL') {
                return;
            }

            $holding = $this->route('holding');

            if (!$holding) {
                return;
            }

            if ((float) $this->input('quantity') > $holding->currentQuantity()) {
                $validator->errors()->add(
                    'quantity',
                    'The sell quantity cannot exceed the current holding quantity.'
                );
            }
        });
    }
}
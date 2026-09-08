<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('type') !== 'SELL') {
                return;
            }

            $transaction = $this->route('transaction');

            if (!$transaction) {
                return;
            }

            $holding = $transaction->holding;

            if (!$holding) {
                return;
            }

            $currentQuantity = $holding->currentQuantity();

            if ($transaction->type === 'SELL') {
                $currentQuantity += (float) $transaction->quantity;
            }

            if ((float) $this->input('quantity') > $currentQuantity) {
                $validator->errors()->add(
                    'quantity',
                    'The sell quantity cannot exceed the current holding quantity.'
                );
            }
        });
    }
}

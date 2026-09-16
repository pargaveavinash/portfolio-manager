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

            $transaction = $this->route('transaction');

            if (!$transaction) {
                return;
            }

            if ($this->input('type') === 'SELL') {
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
            }

            $portfolio = $this->route('portfolio');

            if ($portfolio) {
                $currentCash = $portfolio->cashBalance();

                $oldEffect = 0.0;
                if ($transaction->type === 'BUY') {
                    $oldEffect = -1 * (float) $transaction->quantity * (float) $transaction->price;
                } elseif ($transaction->type === 'SELL') {
                    $oldEffect = (float) $transaction->quantity * (float) $transaction->price;
                }

                $newEffect = 0.0;
                if ($this->input('type') === 'BUY') {
                    $newEffect = -1 * (float) $this->input('quantity') * (float) $this->input('price');
                } elseif ($this->input('type') === 'SELL') {
                    $newEffect = (float) $this->input('quantity') * (float) $this->input('price');
                }

                $cashAfterUpdate = $currentCash - $oldEffect + $newEffect;

                if (round($cashAfterUpdate, 4) < 0) {
                    $validator->errors()->add(
                        'quantity',
                        'Insufficient cash balance to update this transaction.'
                    );
                }
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCashTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $portfolio = $this->route('portfolio');

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
                Rule::in([$portfolio ? $portfolio->base_currency : '']),
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('type') !== 'WITHDRAWAL') {
                return;
            }

            $portfolio = $this->route('portfolio');

            if (!$portfolio) {
                return;
            }

            if ((float) $this->input('amount') > $portfolio->cashBalance()) {
                $validator->errors()->add(
                    'amount',
                    'The withdrawal amount cannot exceed the current cash balance.'
                );
            }
        });
    }
}
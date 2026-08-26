<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                'max:30',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'asset_type' => [
                'required',
                'string',
                Rule::in([
                    'stock',
                    'etf',
                    'mutual_fund',
                ]),
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'average_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],
        ];
    }
}
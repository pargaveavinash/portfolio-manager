<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Http\Resources\CashTransactionResource;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CashTransactionController extends Controller
{
    public function store(
        StoreCashTransactionRequest $request,
        Portfolio $portfolio
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        $cashTransaction = $portfolio->cashTransactions()->create(
            $request->validated()
        );

        return response()->json([
            'data' => [
                'cash_transaction' => new CashTransactionResource($cashTransaction),
            ],
        ], 201);
    }
}
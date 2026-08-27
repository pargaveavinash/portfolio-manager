<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;


class TransactionController extends Controller
{
    public function store(
        StoreTransactionRequest $request,
        Portfolio $portfolio,
        Holding $holding
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        $transaction = $holding->transactions()->create([
            ...$request->validated(),
            'portfolio_id' => $portfolio->id,
        ]);

        return response()->json([
            'data' => [
                'transaction' => new TransactionResource($transaction),
            ],
        ], 201);
    }

    public function index(Portfolio $portfolio): JsonResponse
    {
        Gate::authorize('view', $portfolio);

        $transactions = $portfolio->transactions()
            ->latest('transaction_date')
            ->get();

        return response()->json([
            'data' => [
                'transactions' => TransactionResource::collection($transactions),
            ],
        ]);
    }

    public function show(
        Portfolio $portfolio,
        Transaction $transaction
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        if ($transaction->portfolio_id !== $portfolio->id) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'transaction' => new TransactionResource($transaction),
            ],
        ]);
    }

    public function update(
        UpdateTransactionRequest $request,
        Portfolio $portfolio,
        Transaction $transaction
    ): JsonResponse {
        Gate::authorize('update', $transaction);

        if ($transaction->portfolio_id !== $portfolio->id) {
            abort(404);
        }

        $transaction->update($request->validated());

        return response()->json([
            'data' => [
                'transaction' => new TransactionResource($transaction),
            ],
        ]);
    }

    public function destroy(
        Portfolio $portfolio,
        Transaction $transaction
    ): Response {
        Gate::authorize('delete', $transaction);

        if ($transaction->portfolio_id !== $portfolio->id) {
            abort(404);
        }

        $transaction->delete();

        return response()->noContent();
    }
}
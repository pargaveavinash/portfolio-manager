<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHoldingRequest;
use App\Models\Holding;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HoldingController extends Controller
{
    public function store(
        StoreHoldingRequest $request,
        Portfolio $portfolio
    ): JsonResponse {
        abort_unless(
            $portfolio->user_id === $request->user()->id,
            403
        );

        $holding = $portfolio->holdings()->create(
            $request->validated()
        );

        return response()->json([
            'data' => [
                'holding' => $holding,
            ],
        ], 201);
    }

    public function index(Portfolio $portfolio): JsonResponse
    {
        Gate::authorize('view', $portfolio);

        return response()->json([
            'data' => [
                'holdings' => $portfolio->holdings()->get(),
            ],
        ]);
    }

    public function show(Portfolio $portfolio, Holding $holding): JsonResponse
    {
        Gate::authorize('view', $portfolio);

        abort_unless(
            $holding->portfolio_id === $portfolio->id,
            404
        );

        return response()->json([
            'data' => [
                'holding' => $holding,
            ],
        ]);
    }

    public function update(
        Request $request,
        Portfolio $portfolio,
        Holding $holding
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        abort_unless(
            $holding->portfolio_id === $portfolio->id,
            404
        );

        $validated = $request->validate([
            'quantity'      => ['sometimes', 'numeric', 'gt:0'],
            'average_price' => ['sometimes', 'numeric', 'gte:0'],
        ]);

        $holding->update($validated);

        return response()->json([
            'data' => [
                'holding' => $holding->fresh(),
            ],
        ]);
    }

    public function destroy(Portfolio $portfolio, Holding $holding): Response
    {
        Gate::authorize('view', $portfolio);

        abort_unless(
            $holding->portfolio_id === $portfolio->id,
            404
        );

        $holding->delete();

        return response()->noContent();
    }
}

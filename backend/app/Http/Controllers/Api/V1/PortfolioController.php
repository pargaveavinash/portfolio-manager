<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioRequest;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\PortfolioResource;

class PortfolioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $portfolios = $request->user()
            ->portfolios()
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'portfolios' => PortfolioResource::collection($portfolios),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $portfolio = $request->user()->portfolios()->create(
            $request->validated()
        );

        return response()->json([
            'data' => [
                'portfolio' => new PortfolioResource($portfolio),
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless(
            $request->user()->can('view', $portfolio),
            404
        );

        return response()->json([
            'data' => [
                'portfolio' => new PortfolioResource($portfolio),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdatePortfolioRequest $request,
        Portfolio $portfolio
    ): JsonResponse {
        abort_unless(
            $request->user()->can('update', $portfolio),
            404
        );

        $portfolio->update($request->validated());

        return response()->json([
            'data' => [
                'portfolio' => new PortfolioResource($portfolio->fresh()),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless(
            $request->user()->can('delete', $portfolio),
            404
        );

        $portfolio->delete();

        return response()->json([
            'data' => [
                'message' => 'Portfolio deleted successfully.',
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioDashboardTrendResource;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioDashboardTrendController extends Controller
{
    /**
     * Display the dashboard trend analytics for the specified portfolio.
     */
    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless(
            $request->user()->can('view', $portfolio),
            404
        );

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $cacheService = app(\App\Services\PortfolioCacheService::class);
        $trendsData = $cacheService->getDashboardTrends(
            $portfolio,
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'data' => $trendsData,
        ]);
    }
}

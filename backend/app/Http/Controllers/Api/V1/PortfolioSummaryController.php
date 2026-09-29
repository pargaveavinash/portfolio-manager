<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\PortfolioCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PortfolioSummaryController extends Controller
{
    private PortfolioCacheService $cacheService;

    public function __construct(PortfolioCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Display the portfolio summary.
     */
    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless(
            $request->user()->can('view', $portfolio),
            404
        );

        $data = $this->cacheService->getSummary($portfolio);

        return response()->json([
            'data' => $data,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioSummaryResource;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PortfolioSummaryController extends Controller
{
    /**
     * Display the portfolio summary.
     */
    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless(
            $request->user()->can('view', $portfolio),
            404
        );

        return response()->json([
            'data' => new PortfolioSummaryResource($portfolio),
        ]);
    }
}

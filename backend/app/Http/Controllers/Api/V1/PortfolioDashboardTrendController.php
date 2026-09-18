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

        $query = $portfolio->snapshots()->orderBy('valuation_date', 'asc');

        if (!empty($validated['from'])) {
            $query->whereDate('valuation_date', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('valuation_date', '<=', $validated['to']);
        }

        $snapshots = $query->get();

        return response()->json([
            'data' => [
                'trends' => PortfolioDashboardTrendResource::collection($snapshots),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioHistoryResource;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortfolioHistoryController extends Controller
{
    /**
     * Display the portfolio history (snapshots).
     */
    public function index(Request $request, Portfolio $portfolio): JsonResponse
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
                'history' => PortfolioHistoryResource::collection($snapshots),
            ],
        ]);
    }
}

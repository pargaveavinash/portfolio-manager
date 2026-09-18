<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioDashboardResource;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortfolioDashboardController extends Controller
{
    /**
     * Display the dashboard for the specified portfolio.
     */
    public function show(Request $request, Portfolio $portfolio)
    {
        abort_unless(
            $request->user()->can('view', $portfolio),
            404
        );

        $portfolio->loadMissing([
            'holdings.transactions',
            'cashTransactions',
            'allocationTargets',
        ]);

        return new PortfolioDashboardResource($portfolio);
    }
}

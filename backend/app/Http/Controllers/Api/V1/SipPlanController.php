<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetSipPlanRequest;
use App\Models\Portfolio;
use App\Services\SipPlanner\SipPlannerService;
use Illuminate\Http\JsonResponse;

class SipPlanController extends Controller
{
    public function __construct(
        private readonly SipPlannerService $sipPlannerService
    ) {}

    public function show(GetSipPlanRequest $request, Portfolio $portfolio): JsonResponse
    {
        abort_unless(
            $request->user()->can('view', $portfolio),
            404
        );

        if (!$portfolio->hasValidAllocation()) {
            return response()->json([
                'message' => 'Portfolio does not have a valid 100% allocation.'
            ], 422);
        }

        $amount = (float) $request->validated('amount');
        $strategy = $request->validated('strategy', 'optimization');

        $plan = $this->sipPlannerService->generatePlan($portfolio, $amount, $strategy);

        return response()->json([
            'data' => [
                'sip_amount' => $amount,
                'strategy' => $strategy,
                'plan' => $plan,
            ]
        ]);
    }
}

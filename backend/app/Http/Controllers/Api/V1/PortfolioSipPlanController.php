<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSipPlanRequest;
use App\Http\Requests\UpdateSipPlanRequest;
use App\Http\Resources\SipPlanResource;
use App\Models\Portfolio;
use App\Models\SipPlan;
use App\Services\SipScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PortfolioSipPlanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SipScheduleService $sipScheduleService
    ) {}

    public function index(Portfolio $portfolio): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [SipPlan::class, $portfolio]);

        return SipPlanResource::collection(
            $portfolio->sipPlans()->latest()->get()
        );
    }

    public function store(StoreSipPlanRequest $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('create', [SipPlan::class, $portfolio]);

        $validated = $request->validated();

        $timezone = $validated['timezone'] ?? 'UTC';
        $strategy = $validated['strategy'] ?? 'optimization';

        $nextScheduledDate = $this->sipScheduleService->calculateNextScheduledDate(
            $validated['start_date'],
            $validated['frequency'],
            $timezone
        );

        $sipPlan = $portfolio->sipPlans()->create([
            'amount' => $validated['amount'],
            'strategy' => $strategy,
            'frequency' => $validated['frequency'],
            'status' => 'active',
            'start_date' => $validated['start_date'],
            'next_scheduled_date' => $nextScheduledDate,
            'end_date' => $validated['end_date'] ?? null,
            'timezone' => $timezone,
        ]);

        return response()->json([
            'data' => new SipPlanResource($sipPlan)
        ], 201);
    }

    public function show(SipPlan $sipPlan): SipPlanResource
    {
        $this->authorize('view', $sipPlan);

        return new SipPlanResource($sipPlan);
    }

    public function update(UpdateSipPlanRequest $request, SipPlan $sipPlan): SipPlanResource
    {
        $this->authorize('update', $sipPlan);

        $validated = $request->validated();

        // If start_date, frequency or timezone changes, recalculate next_scheduled_date
        $recalculateSchedule = isset($validated['start_date']) || isset($validated['frequency']) || isset($validated['timezone']);

        if ($recalculateSchedule) {
            $startDate = $validated['start_date'] ?? $sipPlan->start_date->toDateString();
            $frequency = $validated['frequency'] ?? $sipPlan->frequency;
            $timezone = $validated['timezone'] ?? $sipPlan->timezone;

            $validated['next_scheduled_date'] = $this->sipScheduleService->calculateNextScheduledDate(
                $startDate,
                $frequency,
                $timezone
            );
        }

        $sipPlan->update($validated);

        return new SipPlanResource($sipPlan);
    }

    public function destroy(SipPlan $sipPlan): JsonResponse
    {
        $this->authorize('delete', $sipPlan);

        $sipPlan->delete();

        return response()->json(null, 204);
    }
}

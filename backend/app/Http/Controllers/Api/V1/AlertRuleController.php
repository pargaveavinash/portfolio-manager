<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Models\AlertRule;
use App\Models\Holding;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AlertRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rules = AlertRule::where('user_id', $request->user()->id)->get();
        return response()->json(['data' => $rules]);
    }

    public function store(StoreAlertRuleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Verify ownership of the referenced entity
        if ($validated['reference_type'] === 'portfolio') {
            $portfolio = Portfolio::findOrFail($validated['reference_id']);
            if ($portfolio->user_id !== $request->user()->id) {
                throw new AccessDeniedHttpException('This portfolio does not belong to you.');
            }
        } elseif ($validated['reference_type'] === 'holding') {
            $holding = Holding::with('portfolio')->findOrFail($validated['reference_id']);
            if ($holding->portfolio->user_id !== $request->user()->id) {
                throw new AccessDeniedHttpException('This holding does not belong to you.');
            }
        }

        $validated['user_id'] = $request->user()->id;
        $rule = AlertRule::create($validated);

        return response()->json(['data' => $rule], 201);
    }

    public function show(Request $request, AlertRule $rule): JsonResponse
    {
        if ($rule->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException();
        }

        return response()->json(['data' => $rule]);
    }

    public function update(UpdateAlertRuleRequest $request, AlertRule $rule): JsonResponse
    {
        if ($rule->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException();
        }

        $rule->update($request->validated());

        return response()->json(['data' => $rule]);
    }

    public function toggle(Request $request, AlertRule $rule): JsonResponse
    {
        if ($rule->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException();
        }

        $request->validate(['is_active' => 'required|boolean']);
        
        $rule->update(['is_active' => $request->is_active]);

        return response()->json(['data' => $rule]);
    }

    public function destroy(Request $request, AlertRule $rule): JsonResponse
    {
        if ($rule->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException();
        }

        $rule->delete();

        return response()->json(null, 204);
    }
}

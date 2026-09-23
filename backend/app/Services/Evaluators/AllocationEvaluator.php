<?php

namespace App\Services\Evaluators;

use App\Models\AlertNotification;
use App\Models\AlertRule;
use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

class AllocationEvaluator implements AlertEvaluatorInterface
{
    public function evaluate(AlertRule $rule): void
    {
        if ($rule->reference_type !== 'portfolio') {
            return;
        }

        $portfolio = Portfolio::with('holdings')->find($rule->reference_id);
        if (!$portfolio) {
            return; // Skip if missing/deleted
        }

        $deviated = false;
        $deviatedSymbols = [];

        // We check all unique symbols in the portfolio
        $symbols = $portfolio->holdings->pluck('symbol')->unique();

        foreach ($symbols as $symbol) {
            $action = $portfolio->rebalancingAction($symbol);
            if ($action === 'BUY' || $action === 'SELL') {
                $deviated = true;
                $deviatedSymbols[] = $symbol;
            }
        }

        if ($deviated) {
            if (!$rule->last_evaluated_state) {
                // FALSE -> TRUE: create notification and transition state
                DB::transaction(function () use ($rule, $deviatedSymbols) {
                    $updated = AlertRule::where('id', $rule->id)
                        ->where('last_evaluated_state', false)
                        ->update(['last_evaluated_state' => true]);
                    
                    if ($updated) {
                        AlertNotification::create([
                            'alert_rule_id' => $rule->id,
                            'user_id' => $rule->user_id,
                            'message' => 'Allocation deviation detected for: ' . implode(', ', $deviatedSymbols),
                            'triggered_at' => now(),
                        ]);
                        $rule->last_evaluated_state = true;
                    }
                });
            }
            // TRUE -> TRUE: do nothing (idempotency)
        } else {
            if ($rule->last_evaluated_state) {
                // TRUE -> FALSE: reset state
                AlertRule::where('id', $rule->id)
                    ->where('last_evaluated_state', true)
                    ->update(['last_evaluated_state' => false]);
                $rule->last_evaluated_state = false;
            }
        }
    }
}

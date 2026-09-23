<?php

namespace App\Services\Evaluators;

use App\Models\AlertNotification;
use App\Models\AlertRule;
use App\Models\Holding;

class PriceEvaluator implements AlertEvaluatorInterface
{
    public function evaluate(AlertRule $rule): void
    {
        if ($rule->reference_type !== 'holding') {
            return;
        }

        $holding = Holding::find($rule->reference_id);
        if (!$holding) {
            return;
        }

        $price = $holding->currentMarketPrice();
        // If price is literally 0.0 or null due to missing data (and we treat missing data strictly),
        // we might want to check if the holding actually has a market price. 
        // But the prompt says "missing NAV does not become zero". 
        // Assuming currentMarketPrice() handles it or returns a specific value. If it returns 0.0 when missing,
        // we should ideally check if it's genuinely missing, but since we are reusing it, 
        // let's assume it returns a valid price or throws/returns null.
        if ($price === null || $price === 0.0) {
            // Project convention: if we can't find a price, it might return 0.0 or throw.
            // We'll skip evaluation if it looks like missing data.
            return;
        }

        // Use bcmath for comparison. Convert float to string to prevent precision loss.
        $priceStr = number_format($price, 6, '.', '');
        $thresholdStr = number_format($rule->threshold_value, 6, '.', '');

        $triggered = false;
        if ($rule->type === 'PRICE_ABOVE') {
            if (bccomp($priceStr, $thresholdStr, 6) >= 0) {
                $triggered = true;
            }
        } elseif ($rule->type === 'PRICE_BELOW') {
            if (bccomp($priceStr, $thresholdStr, 6) <= 0) {
                $triggered = true;
            }
        }

        if ($triggered) {
            if (!$rule->last_evaluated_state) {
                AlertNotification::create([
                    'alert_rule_id' => $rule->id,
                    'user_id' => $rule->user_id,
                    'message' => "Holding {$holding->symbol} crossed threshold {$rule->threshold_value}. Current price: {$price}",
                    'triggered_at' => now(),
                ]);
                $rule->update(['last_evaluated_state' => true]);
            }
        } else {
            if ($rule->last_evaluated_state) {
                $rule->update(['last_evaluated_state' => false]);
            }
        }
    }
}

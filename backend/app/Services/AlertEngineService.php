<?php

namespace App\Services;

use App\Models\AlertRule;
use App\Services\Evaluators\AllocationEvaluator;
use App\Services\Evaluators\PriceEvaluator;

class AlertEngineService
{
    protected array $evaluators;

    public function __construct(
        AllocationEvaluator $allocationEvaluator,
        PriceEvaluator $priceEvaluator
    ) {
        $this->evaluators = [
            'ALLOCATION' => $allocationEvaluator,
            'PRICE_ABOVE' => $priceEvaluator,
            'PRICE_BELOW' => $priceEvaluator,
            // SIP_REMINDER is out of scope until persistence is implemented
        ];
    }

    public function evaluateRule(AlertRule $rule): void
    {
        if (isset($this->evaluators[$rule->type])) {
            try {
                $this->evaluators[$rule->type]->evaluate($rule);
            } catch (\App\Exceptions\MissingMarketDataException $e) {
                // Skip evaluation for this cycle as per financial safety rules
            }
        }
    }

    public function evaluate(): void
    {
        $rules = AlertRule::where('is_active', true)->get();

        foreach ($rules as $rule) {
            $this->evaluateRule($rule);
        }
    }
}

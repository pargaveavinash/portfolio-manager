<?php

namespace App\Services\Evaluators;

use App\Models\AlertRule;

interface AlertEvaluatorInterface
{
    public function evaluate(AlertRule $rule): void;
}

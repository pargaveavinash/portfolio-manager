<?php

namespace App\Domain\Evaluation;

use App\Models\MutualFund;

class EvaluationResult
{
    public function __construct(
        private readonly MutualFund $mutualFund,
        private readonly string $family,
        private readonly array $metrics
    ) {
    }

    public function getMutualFund(): MutualFund
    {
        return $this->mutualFund;
    }

    public function getFamily(): string
    {
        return $this->family;
    }

    /**
     * @return MetricResult[]
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }
}

<?php

namespace App\Domain\Evaluation;

class EvaluationProfile
{
    public function __construct(
        private readonly string $family,
        private readonly array $expectedMetrics
    ) {
    }

    public function getFamily(): string
    {
        return $this->family;
    }

    public function getExpectedMetrics(): array
    {
        return $this->expectedMetrics;
    }
}

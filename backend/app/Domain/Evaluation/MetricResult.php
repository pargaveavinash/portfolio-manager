<?php

namespace App\Domain\Evaluation;

class MetricResult
{
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_INSUFFICIENT_DATA = 'INSUFFICIENT_DATA';
    public const STATUS_DATA_UNAVAILABLE = 'DATA_UNAVAILABLE';
    public const STATUS_NOT_APPLICABLE = 'NOT_APPLICABLE';

    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly ?float $value,
        public readonly ?string $reason = null
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}

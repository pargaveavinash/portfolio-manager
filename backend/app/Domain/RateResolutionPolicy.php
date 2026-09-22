<?php

namespace App\Domain;

class RateResolutionPolicy
{
    private int $maxLookbackDays;
    private ?float $fallbackRate;
    private bool $fallbackAllowed;

    private function __construct(int $maxLookbackDays, bool $fallbackAllowed, ?float $fallbackRate)
    {
        $this->maxLookbackDays = $maxLookbackDays;
        $this->fallbackAllowed = $fallbackAllowed;
        $this->fallbackRate = $fallbackRate;
    }

    public static function strict(): self
    {
        return new self(0, false, null);
    }

    public static function lookback(int $days): self
    {
        return new self($days, false, null);
    }

    public static function fallback(float $rate): self
    {
        return new self(0, true, $rate);
    }

    public static function lookbackWithFallback(int $days, float $rate): self
    {
        return new self($days, true, $rate);
    }

    public function getMaxLookbackDays(): int
    {
        return $this->maxLookbackDays;
    }

    public function isFallbackAllowed(): bool
    {
        return $this->fallbackAllowed;
    }

    public function getFallbackRate(): ?float
    {
        return $this->fallbackRate;
    }
}

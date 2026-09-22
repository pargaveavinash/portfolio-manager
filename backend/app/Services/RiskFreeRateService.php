<?php

namespace App\Services;

use App\Domain\RateResolutionPolicy;
use App\Exceptions\MissingMarketDataException;
use App\Models\RiskFreeRate;
use Carbon\Carbon;
use InvalidArgumentException;

class RiskFreeRateService
{
    public const CONVENTION_SIMPLE_NOMINAL_ACTUAL_365 = 'SIMPLE_NOMINAL_ACTUAL_365';
    public const CONVENTION_EFFECTIVE_ANNUAL_365 = 'EFFECTIVE_ANNUAL_365';

    /**
     * Resolves the applicable rate for a given date using the provided policy.
     *
     * @throws MissingMarketDataException
     */
    public function resolveRate(RiskFreeRate $rate, $valuationDate, RateResolutionPolicy $policy): float
    {
        $date = Carbon::parse($valuationDate);
        $lookbackLimit = $date->copy()->subDays($policy->getMaxLookbackDays());

        $observation = $rate->values()
            ->where('valuation_date', '<=', $date->copy()->endOfDay())
            ->where('valuation_date', '>=', $lookbackLimit->copy()->startOfDay())
            ->orderBy('valuation_date', 'desc')
            ->first();

        if ($observation) {
            return (float) $observation->rate;
        }

        if ($policy->isFallbackAllowed() && $policy->getFallbackRate() !== null) {
            return $policy->getFallbackRate();
        }

        throw new MissingMarketDataException(
            "No risk-free rate found for {$rate->code} on or before {$date->format('Y-m-d')} within lookback period."
        );
    }

    /**
     * Calculates the daily effective rate given an annualized yield using the effective annual 365 convention.
     */
    public function calculateDailyEffectiveRate(float $annualizedRate): float
    {
        return pow(1 + $annualizedRate, 1 / 365) - 1;
    }

    /**
     * Calculates the daily simple nominal rate given an annualized yield using actual 365 convention.
     */
    public function calculateDailySimpleNominalRate(float $annualizedRate): float
    {
        return $annualizedRate / 365;
    }

    /**
     * Helper to compute the period return for a single static rate over a number of days.
     * (Defaults to EFFECTIVE_ANNUAL_365 for tests that use this directly without dates).
     */
    public function calculatePeriodReturn(float $annualizedRate, int $days, string $convention = self::CONVENTION_EFFECTIVE_ANNUAL_365): float
    {
        if ($convention === self::CONVENTION_SIMPLE_NOMINAL_ACTUAL_365) {
            return pow(1 + ($annualizedRate / 365), $days) - 1;
        }

        if ($convention === self::CONVENTION_EFFECTIVE_ANNUAL_365) {
            return pow(1 + $annualizedRate, $days / 365) - 1;
        }

        throw new InvalidArgumentException("Invalid conversion convention.");
    }

    /**
     * Calculates the compounded period return between two dates, supporting rate changes.
     */
    public function calculatePeriodReturnBetweenDates(
        RiskFreeRate $rate,
        $startDate,
        $endDate,
        RateResolutionPolicy $policy,
        string $convention = self::CONVENTION_EFFECTIVE_ANNUAL_365
    ): float {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->greaterThanOrEqualTo($end)) {
            throw new InvalidArgumentException('Start date must be strictly before end date.');
        }

        // Load all relevant observations in bulk
        // We need the resolved rate as of the start date, and any changes up to the end date (exclusive).
        $lookbackLimit = $start->copy()->subDays($policy->getMaxLookbackDays());
        
        $observations = $rate->values()
            ->where('valuation_date', '<', $end->copy()->startOfDay())
            ->where('valuation_date', '>=', $lookbackLimit->copy()->startOfDay())
            ->orderBy('valuation_date', 'asc')
            ->get();

        $currentDate = $start->copy();
        $periodFactor = 1.0;

        while ($currentDate->lessThan($end)) {
            // Find the most recent observation on or before currentDate that is within the lookback window.
            $currentLookbackLimit = $currentDate->copy()->subDays($policy->getMaxLookbackDays());
            
            $applicableObservation = $observations
                ->where('valuation_date', '<=', $currentDate->copy()->endOfDay())
                ->where('valuation_date', '>=', $currentLookbackLimit->copy()->startOfDay())
                ->last();

            $applicableRate = null;
            if ($applicableObservation) {
                $applicableRate = (float) $applicableObservation->rate;
            } elseif ($policy->isFallbackAllowed() && $policy->getFallbackRate() !== null) {
                $applicableRate = $policy->getFallbackRate();
            } else {
                throw new MissingMarketDataException(
                    "No risk-free rate found for {$rate->code} on or before {$currentDate->format('Y-m-d')} within lookback period."
                );
            }

            // Find the date of the NEXT observation strictly after currentDate.
            // If there is one, we compound up to that date (or the end date, whichever is earlier).
            // If not, we compound up to the end date.
            $nextObservation = $observations
                ->where('valuation_date', '>', $currentDate->copy()->endOfDay())
                ->first();

            $nextChangeDate = $nextObservation ? Carbon::parse($nextObservation->valuation_date) : $end;
            if ($nextChangeDate->greaterThan($end)) {
                $nextChangeDate = $end;
            }

            $daysInSegment = $currentDate->diffInDays($nextChangeDate);

            // Compound the segment
            $segmentFactor = 1 + $this->calculatePeriodReturn($applicableRate, $daysInSegment, $convention);
            $periodFactor *= $segmentFactor;

            // Move to the next segment
            $currentDate = $nextChangeDate;
        }

        return $periodFactor - 1;
    }

    /**
     * Calculates the annualized CAGR using the application's analytical 365.25 convention.
     */
    public function calculateAnnualizedCagr(float $periodReturn, int $elapsedDays): float
    {
        if ($elapsedDays <= 0) {
            throw new InvalidArgumentException('Elapsed days must be greater than 0.');
        }

        return pow(1 + $periodReturn, 365.25 / $elapsedDays) - 1;
    }

    /**
     * Full method matching user specs: calculate period return and then CAGR.
     */
    public function cagr(
        RiskFreeRate $rate,
        $startDate,
        $endDate,
        RateResolutionPolicy $policy,
        string $convention = self::CONVENTION_EFFECTIVE_ANNUAL_365
    ): float {
        $periodReturn = $this->calculatePeriodReturnBetweenDates($rate, $startDate, $endDate, $policy, $convention);
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $elapsedDays = $start->diffInDays($end);

        return $this->calculateAnnualizedCagr($periodReturn, $elapsedDays);
    }
    
    /**
     * Compatibility method matching the contract name 'periodReturn'.
     */
    public function periodReturn(
        RiskFreeRate $rate,
        $startDate,
        $endDate,
        RateResolutionPolicy $policy,
        string $convention = self::CONVENTION_EFFECTIVE_ANNUAL_365
    ): float {
        return $this->calculatePeriodReturnBetweenDates($rate, $startDate, $endDate, $policy, $convention);
    }
}

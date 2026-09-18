<?php

namespace App\Services;

use App\Exceptions\MissingMarketDataException;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use Illuminate\Support\Carbon;

class MutualFundPerformanceService
{
    /**
     * Resolves the latest NAV on or before the requested valuation date.
     *
     * @param MutualFund $fund
     * @param Carbon $valuationDate
     * @return MutualFundNav
     * @throws MissingMarketDataException
     */
    public function resolveNav(MutualFund $fund, Carbon $valuationDate): MutualFundNav
    {
        $nav = MutualFundNav::where('mutual_fund_id', $fund->id)
            ->where('nav_date', '<=', $valuationDate->format('Y-m-d'))
            ->orderBy('nav_date', 'desc')
            ->first();

        if (!$nav) {
            throw new MissingMarketDataException('No historical NAV available on or before ' . $valuationDate->format('Y-m-d'));
        }


        return $nav;
    }

    /**
     * Calculates the simple period return between two dates.
     *
     * @param MutualFund $fund
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function periodReturn(MutualFund $fund, Carbon $startDate, Carbon $endDate): float
    {
        if ($startDate->greaterThanOrEqualTo($endDate)) {
            throw new \InvalidArgumentException('Start date must be strictly before end date.');
        }

        $startNav = $this->resolveNav($fund, $startDate);
        $endNav = $this->resolveNav($fund, $endDate);

        return ($endNav->nav / $startNav->nav) - 1;
    }

    /**
     * Calculates the Compound Annual Growth Rate (CAGR).
     *
     * @param MutualFund $fund
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     * @throws MissingMarketDataException
     */
    public function cagr(MutualFund $fund, Carbon $startDate, Carbon $endDate): float
    {
        if ($startDate->greaterThanOrEqualTo($endDate)) {
            throw new \InvalidArgumentException('Start date must be strictly before end date for CAGR.');
        }

        $startNav = $this->resolveNav($fund, $startDate);
        $endNav = $this->resolveNav($fund, $endDate);

        if ($startNav->nav <= 0) {
            throw new MissingMarketDataException('Start NAV must be strictly positive for CAGR calculation.');
        }

        $days = $startDate->diffInDays($endDate);
        
        if ($days <= 0) {
            throw new MissingMarketDataException('End date must be strictly after start date for CAGR calculation.');
        }

        $years = $days / 365.25;

        return (float) (pow($endNav->nav / $startNav->nav, 1 / $years) - 1);
    }

    /**
     * Calculates rolling returns for a given window over monthly observation points.
     *
     * @param MutualFund $fund
     * @param Carbon $from
     * @param Carbon $to
     * @param int $windowMonths
     * @return array
     */
    public function rollingReturns(MutualFund $fund, Carbon $from, Carbon $to, int $windowMonths): array
    {
        if ($windowMonths <= 0) {
            throw new \InvalidArgumentException('Window months must be strictly positive.');
        }

        if ($from->greaterThan($to)) {
            throw new \InvalidArgumentException('From date cannot be after to date.');
        }

        $navs = MutualFundNav::where('mutual_fund_id', $fund->id)
            ->where('nav_date', '<=', $to->format('Y-m-d'))
            ->orderBy('nav_date', 'desc')
            ->get();

        $results = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $currentStr = $current->format('Y-m-d');
            $startStr = $current->copy()->subMonthsNoOverflow($windowMonths)->format('Y-m-d');

            $endNav = $navs->first(function ($item) use ($currentStr) {
                return $item->nav_date <= $currentStr;
            });
            $startNav = $navs->first(function ($item) use ($startStr) {
                return $item->nav_date <= $startStr;
            });

            if ($endNav && $startNav) {
                $results[] = [
                    'date' => $currentStr,
                    'return' => ($endNav->nav / $startNav->nav) - 1,
                ];
            }
            
            $current->addMonthNoOverflow();
        }

        return $results;
    }

    /**
     * Calculates the annualized volatility based on daily simple returns.
     *
     * @param MutualFund $fund
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     * @throws MissingMarketDataException
     */
    public function annualizedVolatility(MutualFund $fund, Carbon $startDate, Carbon $endDate): float
    {
        $startNavDate = substr($this->resolveNav($fund, $startDate)->nav_date, 0, 10);
        $endNavDate = substr($this->resolveNav($fund, $endDate)->nav_date, 0, 10);

        $navs = MutualFundNav::where('mutual_fund_id', $fund->id)
            ->whereBetween('nav_date', [$startNavDate, $endNavDate])
            ->orderBy('nav_date', 'asc')
            ->get();

        if ($navs->count() < 2) {
            throw new MissingMarketDataException('Insufficient NAV observations to calculate volatility.');
        }

        $dailyReturns = [];
        $previousNav = null;

        foreach ($navs as $navModel) {
            $currentNav = (float) $navModel->nav;
            if ($previousNav !== null) {
                $dailyReturns[] = ($currentNav / $previousNav) - 1;
            }
            $previousNav = $currentNav;
        }

        $n = count($dailyReturns);
        if ($n < 2) {
            throw new MissingMarketDataException('Insufficient daily returns to calculate variance.');
        }

        $mean = array_sum($dailyReturns) / $n;
        
        $sumSquaredDiffs = 0;
        foreach ($dailyReturns as $r) {
            $sumSquaredDiffs += pow($r - $mean, 2);
        }

        $sampleVariance = $sumSquaredDiffs / ($n - 1);
        $dailyStandardDeviation = sqrt($sampleVariance);

        // Annualize using sqrt(252)
        return $dailyStandardDeviation * sqrt(252);
    }

    /**
     * Calculates the maximum drawdown in the specified period.
     *
     * @param MutualFund $fund
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function maximumDrawdown(MutualFund $fund, Carbon $startDate, Carbon $endDate): float
    {
        $startNavDate = substr($this->resolveNav($fund, $startDate)->nav_date, 0, 10);
        $endNavDate = substr($this->resolveNav($fund, $endDate)->nav_date, 0, 10);

        $navs = MutualFundNav::where('mutual_fund_id', $fund->id)
            ->whereBetween('nav_date', [$startNavDate, $endNavDate])
            ->orderBy('nav_date', 'asc')
            ->get();

        if ($navs->isEmpty()) {
            throw new MissingMarketDataException('No historical NAV available for maximum drawdown calculation.');
        }

        $maxDrawdown = 0.0;
        $runningPeak = 0.0;

        foreach ($navs as $navModel) {
            $currentNav = (float) $navModel->nav;

            if ($currentNav <= 0) {
                throw new MissingMarketDataException('Invalid NAV encountered during maximum drawdown calculation.');
            }

            if ($currentNav > $runningPeak) {
                $runningPeak = $currentNav;
            }

            if ($runningPeak > 0) {
                $drawdown = ($currentNav / $runningPeak) - 1;
                if ($drawdown < $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }
            }
        }

        return $maxDrawdown;
    }
}

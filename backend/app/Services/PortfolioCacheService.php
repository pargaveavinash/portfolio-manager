<?php

namespace App\Services;

use App\Http\Resources\PortfolioDashboardResource;
use App\Http\Resources\PortfolioDashboardTrendResource;
use App\Http\Resources\PortfolioSummaryResource;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Cache;

class PortfolioCacheService
{
    /**
     * Get the cached portfolio summary or calculate and cache it.
     */
    public function getSummary(Portfolio $portfolio): array
    {
        $key = "portfolio:{$portfolio->id}:summary";
        $ttl = 3600; // 1 hour

        return Cache::tags(["portfolio:{$portfolio->id}"])->remember($key, $ttl, function () use ($portfolio) {
            return (new PortfolioSummaryResource($portfolio))->resolve();
        });
    }

    /**
     * Get the cached portfolio dashboard or calculate and cache it.
     */
    public function getDashboard(Portfolio $portfolio): array
    {
        $key = "portfolio:{$portfolio->id}:dashboard";
        $ttl = 3600; // 1 hour

        return Cache::tags(["portfolio:{$portfolio->id}"])->remember($key, $ttl, function () use ($portfolio) {
            $portfolio->loadMissing([
                'holdings.transactions',
                'cashTransactions',
                'allocationTargets',
            ]);

            return json_decode((new PortfolioDashboardResource($portfolio))->toJson(), true);
        });
    }

    /**
     * Get the cached portfolio dashboard trends or calculate and cache it.
     */
    public function getDashboardTrends(Portfolio $portfolio, ?string $from, ?string $to): array
    {
        $fromKey = empty($from) ? 'all' : $from;
        $toKey = empty($to) ? 'all' : $to;
        $key = "portfolio:{$portfolio->id}:dashboard_trends:{$fromKey}:{$toKey}";
        $ttl = 3600; // 1 hour

        return Cache::tags(["portfolio:{$portfolio->id}"])->remember($key, $ttl, function () use ($portfolio, $from, $to) {
            $query = $portfolio->snapshots()->orderBy('valuation_date', 'asc');

            if (!empty($from)) {
                $query->whereDate('valuation_date', '>=', $from);
            }

            if (!empty($to)) {
                $query->whereDate('valuation_date', '<=', $to);
            }

            $snapshots = $query->get();

            return [
                'trends' => json_decode(PortfolioDashboardTrendResource::collection($snapshots)->toJson(), true),
            ];
        });
    }

    /**
     * Invalidate the portfolio summary cache by Portfolio model.
     */
    public function invalidatePortfolio(Portfolio $portfolio): void
    {
        $this->invalidatePortfolioById($portfolio->id);
    }

    /**
     * Invalidate the portfolio summary cache by Portfolio ID.
     */
    public function invalidatePortfolioById(int|string $portfolioId): void
    {
        Cache::tags(["portfolio:{$portfolioId}"])->flush();
    }
}

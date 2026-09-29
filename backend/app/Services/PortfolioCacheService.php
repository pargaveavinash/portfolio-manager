<?php

namespace App\Services;

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

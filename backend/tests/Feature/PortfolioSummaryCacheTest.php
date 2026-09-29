<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PortfolioSummaryCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_summary_is_cached_after_first_request_and_subsequent_request_uses_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create([
            'name'          => 'Cache Test Portfolio',
            'base_currency' => 'INR',
        ]);

        $cacheKey = "portfolio:{$portfolio->id}:summary";

        // Assert cache is empty initially
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        // First request - should cache the result
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary")->assertOk();

        // Assert cache is populated
        $cachedData = Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey);
        $this->assertNotNull($cachedData);
        $this->assertEquals($portfolio->id, $cachedData['portfolio']['id']);
    }

    public function test_transaction_creation_invalidates_portfolio_summary_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Cache Test Portfolio', 'base_currency' => 'INR']);
        $cacheKey = "portfolio:{$portfolio->id}:summary";

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary")->assertOk();
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        // Create a transaction
        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);
        
        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 100,
            'currency' => 'INR',
            'transaction_date' => now(),
        ]);

        // Cache should be invalidated
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_transaction_update_invalidates_portfolio_summary_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Cache Test Portfolio', 'base_currency' => 'INR']);
        $cacheKey = "portfolio:{$portfolio->id}:summary";

        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);
        $transaction = $holding->transactions()->create([
            'portfolio_id' => $portfolio->id, 'type' => 'BUY', 'quantity' => 10, 'price' => 100, 'currency' => 'INR', 'transaction_date' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary")->assertOk();
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        // Update transaction
        $transaction->update(['quantity' => 20]);

        // Cache should be invalidated
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_transaction_deletion_invalidates_portfolio_summary_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Cache Test Portfolio', 'base_currency' => 'INR']);
        $cacheKey = "portfolio:{$portfolio->id}:summary";

        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);
        $transaction = $holding->transactions()->create([
            'portfolio_id' => $portfolio->id, 'type' => 'BUY', 'quantity' => 10, 'price' => 100, 'currency' => 'INR', 'transaction_date' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary")->assertOk();
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        // Delete transaction
        $transaction->delete();

        // Cache should be invalidated
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_transaction_restoration_invalidates_portfolio_summary_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Cache Test Portfolio', 'base_currency' => 'INR']);
        $cacheKey = "portfolio:{$portfolio->id}:summary";

        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);
        $transaction = $holding->transactions()->create([
            'portfolio_id' => $portfolio->id, 'type' => 'BUY', 'quantity' => 10, 'price' => 100, 'currency' => 'INR', 'transaction_date' => now(),
        ]);
        $transaction->delete();

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary")->assertOk();
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        // Restore transaction
        $transaction->restore();

        // Cache should be invalidated
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_portfolio_cache_is_isolated_between_portfolios(): void
    {
        $user = User::factory()->create();
        $portfolio1 = $user->portfolios()->create(['name' => 'Portfolio 1', 'base_currency' => 'INR']);
        $portfolio2 = $user->portfolios()->create(['name' => 'Portfolio 2', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio1->id}/summary")->assertOk();
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio2->id}/summary")->assertOk();

        $cacheKey1 = "portfolio:{$portfolio1->id}:summary";
        $cacheKey2 = "portfolio:{$portfolio2->id}:summary";

        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio1->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio2->id}"])->get($cacheKey2));

        // Create transaction in portfolio 1
        $holding = $portfolio1->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);
        $holding->transactions()->create([
            'portfolio_id' => $portfolio1->id, 'type' => 'BUY', 'quantity' => 10, 'price' => 100, 'currency' => 'INR', 'transaction_date' => now(),
        ]);

        // Cache 1 should be invalidated, Cache 2 should remain
        $this->assertNull(Cache::tags(["portfolio:{$portfolio1->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio2->id}"])->get($cacheKey2));
    }

    public function test_subsequent_request_returns_stale_data_if_db_updated_without_cache_invalidation(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Cache Test Portfolio', 'base_currency' => 'INR']);
        $cacheKey = "portfolio:{$portfolio->id}:summary";

        // First request calculates everything as 0
        $response1 = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary");
        $response1->assertOk()->assertJsonPath('data.summary.total_invested_cost', 0);

        // Manually update the cache to fake a stale state (simulating a calculation that wasn't invalidated)
        $staleData = Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey);
        $staleData['summary']['total_invested_cost'] = 9999;
        Cache::tags(["portfolio:{$portfolio->id}"])->put($cacheKey, $staleData, 3600);

        // Second request should return 9999 since we bypassed invalidation
        $response2 = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/summary");
        $response2->assertOk()->assertJsonPath('data.summary.total_invested_cost', 9999);
    }
}

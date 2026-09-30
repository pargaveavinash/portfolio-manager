<?php

namespace Tests\Feature;

use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\PortfolioAllocation;
use App\Models\PortfolioSnapshot;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioDashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_dashboard_request_populates_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $cacheKey = "portfolio:{$portfolio->id}:dashboard";
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $response->assertStatus(200);

        $cachedData = Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey);
        $this->assertNotNull($cachedData);
        
        $this->assertEquals($response->json('data'), $cachedData);
    }

    public function test_repeated_dashboard_request_uses_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $cacheKey = "portfolio:{$portfolio->id}:dashboard";
        
        // Populate cache with dummy data
        $dummyData = ['dummy' => 'dashboard_data'];
        Cache::tags(["portfolio:{$portfolio->id}"])->put($cacheKey, $dummyData, 3600);

        DB::enableQueryLog();
        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $response->assertStatus(200);
        
        // Verify it returned our cached data
        $this->assertEquals($dummyData, $response->json('data'));

        // Since we injected the cache, it shouldn't query the DB to build the dashboard
        // Note: The auth/policy might still execute some queries (like finding the portfolio).
        // Let's just assert the data matches to prove it served from cache.
        $this->assertFalse(isset($response->json('data')['portfolio'])); // Dummy data doesn't have it
    }

    public function test_dashboard_api_response_remains_identical()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);
        $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);
        
        // 1. Clear Cache
        Cache::tags(["portfolio:{$portfolio->id}"])->flush();
        
        // 2. Uncached request
        $response1 = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $response1->assertStatus(200);
        $uncachedData = $response1->json();
        
        // 3. Cached request
        $response2 = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $response2->assertStatus(200);
        $cachedData = $response2->json();

        $this->assertEquals($uncachedData, $cachedData);
    }

    public function test_dashboard_portfolio_cache_isolation()
    {
        $user = User::factory()->create();
        $portfolio1 = $user->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        $portfolio2 = $user->portfolios()->create(['name' => 'P2', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio1->id}/dashboard");
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio2->id}/dashboard");

        $cacheKey1 = "portfolio:{$portfolio1->id}:dashboard";
        $cacheKey2 = "portfolio:{$portfolio2->id}:dashboard";

        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio1->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio2->id}"])->get($cacheKey2));

        // Invalidate P1
        $portfolio1->holdings()->create([
            'symbol' => 'TCS', 'name' => 'TCS', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->assertNull(Cache::tags(["portfolio:{$portfolio1->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio2->id}"])->get($cacheKey2));
    }

    public function test_unauthorized_user_cannot_access_another_portfolios_cached_dashboard()
    {
        $user1 = User::factory()->create();
        $portfolio = $user1->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        
        // Cache it for user1
        $this->actingAs($user1)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard")->assertStatus(200);

        // User2 tries to access
        $user2 = User::factory()->create();
        $this->actingAs($user2)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard")->assertStatus(404);
    }

    // Invalidation Tests
    
    public function test_transaction_creation_invalidates_dashboard_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);
        $holding = $portfolio->holdings()->create([
            'symbol' => 'TCS', 'name' => 'TCS', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $cacheKey = "portfolio:{$portfolio->id}:dashboard";
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 3000,
            'currency' => 'INR',
            'transaction_date' => now()
        ]);

        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_holding_creation_invalidates_dashboard_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $cacheKey = "portfolio:{$portfolio->id}:dashboard";
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        $portfolio->holdings()->create([
            'symbol' => 'TCS', 'name' => 'TCS', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_portfolio_allocation_creation_invalidates_dashboard_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard");
        $cacheKey = "portfolio:{$portfolio->id}:dashboard";
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        $portfolio->allocationTargets()->create([
            'symbol' => 'TCS',
            'target_percentage' => 100.0
        ]);

        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    // Dashboard Trends Tests

    public function test_first_trend_request_populates_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);
        PortfolioSnapshot::factory()->create([
            'portfolio_id' => $portfolio->id, 
            'valuation_date' => '2023-01-01', 
        ]);

        $cacheKey = "portfolio:{$portfolio->id}:dashboard_trends:all:all";
        $this->assertNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard/trends");
        $response->assertStatus(200);

        $cachedData = Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey);
        $this->assertNotNull($cachedData);
        $this->assertEquals($response->json('data'), $cachedData);
    }

    public function test_repeated_trend_request_uses_cache()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $cacheKey = "portfolio:{$portfolio->id}:dashboard_trends:all:all";
        
        $dummyData = ['trends' => ['dummy_trend_data']];
        Cache::tags(["portfolio:{$portfolio->id}"])->put($cacheKey, $dummyData, 3600);

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard/trends");
        $response->assertStatus(200);
        
        $this->assertEquals($dummyData, $response->json('data'));
    }

    public function test_different_dates_produce_different_cache_entries()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard/trends?from=2023-01-01&to=2023-12-31")->assertStatus(200);
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard/trends?from=2023-06-01&to=2023-12-31")->assertStatus(200);

        $cacheKey1 = "portfolio:{$portfolio->id}:dashboard_trends:2023-01-01:2023-12-31";
        $cacheKey2 = "portfolio:{$portfolio->id}:dashboard_trends:2023-06-01:2023-12-31";

        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey2));
    }

    public function test_missing_date_parameters_handled_deterministically()
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test Portfolio', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/dashboard/trends?from=2023-01-01")->assertStatus(200);
        
        $cacheKey = "portfolio:{$portfolio->id}:dashboard_trends:2023-01-01:all";
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio->id}"])->get($cacheKey));
    }

    public function test_trend_cache_isolation()
    {
        $user = User::factory()->create();
        $portfolio1 = $user->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        $portfolio2 = $user->portfolios()->create(['name' => 'P2', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio1->id}/dashboard/trends");
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio2->id}/dashboard/trends");

        $cacheKey1 = "portfolio:{$portfolio1->id}:dashboard_trends:all:all";
        $cacheKey2 = "portfolio:{$portfolio2->id}:dashboard_trends:all:all";

        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio1->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio2->id}"])->get($cacheKey2));

        // Mutate P1 cache
        $portfolio1->holdings()->create([
            'symbol' => 'TCS', 'name' => 'TCS', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->assertNull(Cache::tags(["portfolio:{$portfolio1->id}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["portfolio:{$portfolio2->id}"])->get($cacheKey2));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Services\PortfolioCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PortfolioRebalancingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebalancing_plan_api_is_cached(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        
        $portfolio->allocationTargets()->create([
            'symbol' => 'RELIANCE',
            'target_percentage' => 100.0,
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:rebalancing_plan";
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $response->assertStatus(200);

        $cachedData = Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey);
        $this->assertNotNull($cachedData);
        $this->assertEquals($response->json('data'), $cachedData);
    }

    public function test_repeated_rebalancing_plan_api_calls_use_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);

        $cacheKey = "{portfolio:{$portfolio->id}}:rebalancing_plan";
        
        $dummyData = [
            [
                'symbol' => 'TCS',
                'target' => 100.0,
                'current' => 0.0,
                'deviation' => -100.0,
                'action' => 'BUY',
                'amount' => 1000.0,
            ]
        ];
        
        Cache::tags(["{portfolio:{$portfolio->id}}"])->put($cacheKey, $dummyData, 3600);

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $response->assertStatus(200);
        
        $this->assertEquals($dummyData, $response->json('data'));
    }

    public function test_cached_rebalancing_plan_exactly_matches_uncached_result(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $portfolio->allocationTargets()->create([
            'symbol' => 'HDFC',
            'target_percentage' => 100.0,
        ]);

        $uncachedResult = $portfolio->rebalancingPlan();

        $response = $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $response->assertStatus(200);

        $cachedResult = Cache::tags(["{portfolio:{$portfolio->id}}"])->get("{portfolio:{$portfolio->id}}:rebalancing_plan");
        
        // Strictly match serialized json_encode/decode output with original uncached method return
        $this->assertEquals($uncachedResult, $cachedResult);
        $this->assertEquals($uncachedResult, $response->json('data'));
    }

    public function test_transaction_creation_invalidates_rebalancing_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $holding = $portfolio->holdings()->create([
            'symbol' => 'HDFC', 'name' => 'HDFC', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:rebalancing_plan";
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $holding->transactions()->create([
            'portfolio_id' => $portfolio->id,
            'type' => 'BUY',
            'quantity' => 10,
            'price' => 1500,
            'transaction_date' => now(),
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_portfolio_allocation_mutation_invalidates_rebalancing_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $cacheKey = "{portfolio:{$portfolio->id}}:rebalancing_plan";
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $allocation = $portfolio->allocationTargets()->create([
            'symbol' => 'TCS',
            'target_percentage' => 50.0
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $allocation->update(['target_percentage' => 60.0]);
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
        
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $allocation->delete();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_holding_mutation_invalidates_rebalancing_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $cacheKey = "{portfolio:{$portfolio->id}}:rebalancing_plan";
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $holding = $portfolio->holdings()->create([
            'symbol' => 'TCS', 'name' => 'TCS', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_rebalancing_cache_isolation_between_two_portfolios(): void
    {
        $user = User::factory()->create();
        $portfolio1 = $user->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        $portfolio2 = $user->portfolios()->create(['name' => 'P2', 'base_currency' => 'INR']);

        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio1->id}/rebalancing");
        $this->actingAs($user)->getJson("/api/v1/portfolios/{$portfolio2->id}/rebalancing");

        $cacheKey1 = "{portfolio:{$portfolio1->id}}:rebalancing_plan";
        $cacheKey2 = "{portfolio:{$portfolio2->id}}:rebalancing_plan";

        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio1->id}}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio2->id}}"])->get($cacheKey2));

        // Mutate P1
        $portfolio1->allocationTargets()->create([
            'symbol' => 'RELIANCE',
            'target_percentage' => 100.0
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio1->id}}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio2->id}}"])->get($cacheKey2));
    }

    public function test_existing_authorization_is_preserved_for_rebalancing(): void
    {
        $user1 = User::factory()->create();
        $portfolio = $user1->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        
        $user2 = User::factory()->create();

        $response = $this->actingAs($user2)->getJson("/api/v1/portfolios/{$portfolio->id}/rebalancing");
        $response->assertStatus(404);

        $cacheKey = "{portfolio:{$portfolio->id}}:rebalancing_plan";
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_unsaved_portfolio_instances_remain_safe(): void
    {
        $portfolio = new Portfolio(['name' => 'New', 'base_currency' => 'INR']);
        $service = new PortfolioCacheService();
        
        $plan = $service->getRebalancingPlan($portfolio);
        $this->assertIsArray($plan);
        $this->assertEmpty($plan);
    }
}

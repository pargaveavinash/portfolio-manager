<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\PortfolioAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AssetAllocationCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocation_methods_are_cached(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create([
            'name'          => 'Allocation Cache Test',
            'base_currency' => 'INR',
        ]);

        $portfolio->allocationTargets()->create([
            'symbol'            => 'RELIANCE',
            'target_percentage' => 40.0,
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:allocationPercentageTotal";
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        // Call method directly
        $total = $portfolio->allocationPercentageTotal();
        $this->assertEquals(40.0, $total);

        // Assert cache is populated
        $cachedValue = Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey);
        $this->assertNotNull($cachedValue);
        $this->assertEquals(40.0, $cachedValue);

        // Check a parameterized method
        $symbolCacheKey = "{portfolio:{$portfolio->id}}:performance:rebalancingAction_RELIANCE";
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($symbolCacheKey));

        $action = $portfolio->rebalancingAction('RELIANCE');
        $this->assertEquals('BUY', $action); // 0% current, 40% target -> BUY

        $cachedAction = Cache::tags(["{portfolio:{$portfolio->id}}"])->get($symbolCacheKey);
        $this->assertNotNull($cachedAction);
        $this->assertEquals('BUY', $cachedAction);
    }

    public function test_repeated_calls_during_request_use_array_memoization_or_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:allocationPercentageTotal";
        Cache::tags(["{portfolio:{$portfolio->id}}"])->put($cacheKey, 99.99, 3600);

        // Because it reads from cache, it should return 99.99
        $this->assertEquals(99.99, $portfolio->allocationPercentageTotal());

        // Even if we change the cache in redis, the array memoization should still return 99.99
        Cache::tags(["{portfolio:{$portfolio->id}}"])->put($cacheKey, 11.11, 3600);
        $this->assertEquals(99.99, $portfolio->allocationPercentageTotal());
    }

    public function test_portfolio_allocation_creation_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $cacheKey = "{portfolio:{$portfolio->id}}:performance:allocationPercentageTotal";

        $portfolio->allocationPercentageTotal();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $portfolio->allocationTargets()->create([
            'symbol'            => 'HDFC',
            'target_percentage' => 50.0,
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_portfolio_allocation_update_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $allocation = $portfolio->allocationTargets()->create([
            'symbol'            => 'HDFC',
            'target_percentage' => 50.0,
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:allocationPercentageTotal";
        $portfolio->allocationPercentageTotal();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $allocation->update(['target_percentage' => 60.0]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_portfolio_allocation_deletion_and_restoration_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $allocation = $portfolio->allocationTargets()->create([
            'symbol'            => 'HDFC',
            'target_percentage' => 50.0,
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:allocationPercentageTotal";
        $portfolio->allocationPercentageTotal();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $allocation->delete();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $portfolio->refresh(); // Clear array cache for next step
        $portfolio->allocationPercentageTotal();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        // Note: Models need SoftDeletes trait for restore/forceDelete to work.
        // Assuming PortfolioAllocation uses SoftDeletes based on standard observer events mentioned in requirement.
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($allocation))) {
            $allocation->restore();
            $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

            $portfolio->refresh();
            $portfolio->allocationPercentageTotal();
            $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

            $allocation->forceDelete();
            $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
        }
    }

    public function test_cache_isolation_between_two_portfolios(): void
    {
        $user = User::factory()->create();
        $portfolio1 = $user->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        $portfolio2 = $user->portfolios()->create(['name' => 'P2', 'base_currency' => 'INR']);

        $portfolio1->allocationPercentageTotal();
        $portfolio2->allocationPercentageTotal();

        $cacheKey1 = "{portfolio:{$portfolio1->id}}:performance:allocationPercentageTotal";
        $cacheKey2 = "{portfolio:{$portfolio2->id}}:performance:allocationPercentageTotal";

        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio1->id}}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio2->id}}"])->get($cacheKey2));

        // Mutate P1
        $portfolio1->allocationTargets()->create([
            'symbol'            => 'INFY',
            'target_percentage' => 10.0,
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio1->id}}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio2->id}}"])->get($cacheKey2));
    }
}

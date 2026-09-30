<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PortfolioPerformanceCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_portfolio_performance_calculations_are_cached(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create([
            'name'          => 'Performance Cache Test',
            'base_currency' => 'INR',
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:currentInvestedCost";

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        // Call method directly
        $cost = $portfolio->currentInvestedCost();
        
        $this->assertEquals(0.0, $cost);

        // Assert cache is populated
        $cachedValue = Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey);
        $this->assertNotNull($cachedValue);
        $this->assertEquals(0.0, $cachedValue);
    }

    public function test_repeated_calls_during_request_use_array_memoization_or_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);

        // Set fake cache value
        $cacheKey = "{portfolio:{$portfolio->id}}:performance:currentInvestedCost";
        Cache::tags(["{portfolio:{$portfolio->id}}"])->put($cacheKey, 999.99, 3600);

        // Because it reads from cache, it should return 999.99
        $this->assertEquals(999.99, $portfolio->currentInvestedCost());
        
        // Even if we change the cache in redis, the array memoization should still return 999.99
        Cache::tags(["{portfolio:{$portfolio->id}}"])->put($cacheKey, 111.11, 3600);
        $this->assertEquals(999.99, $portfolio->currentInvestedCost());
    }

    public function test_unsaved_new_portfolio_instances_do_not_cause_cache_errors(): void
    {
        $portfolio = new Portfolio(['name' => 'New', 'base_currency' => 'INR']);
        
        // This should not throw an exception about empty tags or keys
        $cost = $portfolio->currentInvestedCost();
        $this->assertEquals(0.0, $cost);
    }

    public function test_holding_creation_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $cacheKey = "{portfolio:{$portfolio->id}}:performance:currentInvestedCost";

        $portfolio->currentInvestedCost();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_holding_update_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:currentInvestedCost";
        $portfolio->currentInvestedCost();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $holding->update(['quantity' => 10]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_holding_deletion_and_restoration_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $holding = $portfolio->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:currentInvestedCost";
        $portfolio->currentInvestedCost();
        
        $holding->delete();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $portfolio->refresh(); // Clear array cache for next test step
        $portfolio->currentInvestedCost();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $holding->restore();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
        
        $portfolio->refresh();
        $portfolio->currentInvestedCost();
        $holding->forceDelete();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_cashtransaction_creation_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $cacheKey = "{portfolio:{$portfolio->id}}:performance:cashBalance";

        $portfolio->cashBalance();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT', 'amount' => 1000, 'currency' => 'INR', 'transaction_date' => now()
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_cashtransaction_update_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $tx = $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT', 'amount' => 1000, 'currency' => 'INR', 'transaction_date' => now()
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:cashBalance";
        $portfolio->cashBalance();
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $tx->update(['amount' => 2000]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_cashtransaction_deletion_and_restoration_invalidates_cache(): void
    {
        $user = User::factory()->create();
        $portfolio = $user->portfolios()->create(['name' => 'Test', 'base_currency' => 'INR']);
        $tx = $portfolio->cashTransactions()->create([
            'type' => 'DEPOSIT', 'amount' => 1000, 'currency' => 'INR', 'transaction_date' => now()
        ]);

        $cacheKey = "{portfolio:{$portfolio->id}}:performance:cashBalance";
        $portfolio->cashBalance();
        
        $tx->delete();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));

        $portfolio->refresh();
        $portfolio->cashBalance();
        
        $tx->restore();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
        
        $portfolio->refresh();
        $portfolio->cashBalance();
        $tx->forceDelete();
        $this->assertNull(Cache::tags(["{portfolio:{$portfolio->id}}"])->get($cacheKey));
    }

    public function test_cache_isolation_between_two_portfolios(): void
    {
        $user = User::factory()->create();
        $portfolio1 = $user->portfolios()->create(['name' => 'P1', 'base_currency' => 'INR']);
        $portfolio2 = $user->portfolios()->create(['name' => 'P2', 'base_currency' => 'INR']);

        $portfolio1->currentInvestedCost();
        $portfolio2->currentInvestedCost();

        $cacheKey1 = "{portfolio:{$portfolio1->id}}:performance:currentInvestedCost";
        $cacheKey2 = "{portfolio:{$portfolio2->id}}:performance:currentInvestedCost";

        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio1->id}}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio2->id}}"])->get($cacheKey2));

        // Mutate P1
        $portfolio1->holdings()->create([
            'symbol' => 'RELIANCE', 'name' => 'Reliance', 'asset_type' => 'stock', 'currency' => 'INR',
            'quantity' => 0, 'average_price' => 0
        ]);

        $this->assertNull(Cache::tags(["{portfolio:{$portfolio1->id}}"])->get($cacheKey1));
        $this->assertNotNull(Cache::tags(["{portfolio:{$portfolio2->id}}"])->get($cacheKey2));
    }
}

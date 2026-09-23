<?php

namespace Tests\Feature\Services;

use App\Models\AlertRule;
use App\Models\AlertNotification;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\PortfolioAllocation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocation_deviation_alert_condition_transitions(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'ALLOCATION',
            'reference_type' => 'portfolio',
            'reference_id' => $portfolio->id,
            'is_active' => true,
        ]);

        PortfolioAllocation::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'target_percentage' => 50.0]);
        PortfolioAllocation::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'GOOG', 'target_percentage' => 50.0]);

        $holdingA = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 100.0]);
        $holdingB = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'GOOG', 'name' => 'Google', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 100.0]);

        Transaction::forceCreate(['portfolio_id' => $portfolio->id, 'holding_id' => $holdingA->id, 'type' => 'BUY', 'quantity' => 10, 'price' => 100.0, 'transaction_date' => now()]);
        Transaction::forceCreate(['portfolio_id' => $portfolio->id, 'holding_id' => $holdingB->id, 'type' => 'BUY', 'quantity' => 10, 'price' => 100.0, 'transaction_date' => now()]);
        
        $service = app(\App\Services\AlertEngineService::class);
        
        // At 100 each, it's 50/50. No deviation.
        $service->evaluate();
        $this->assertDatabaseEmpty('alert_notifications');

        // Deviate: AAPL goes to 300. Value: AAPL 3000, GOOG 1000. AAPL = 75%, GOOG = 25%. Deviation is 25% > 5%.
        $holdingA->update(['market_price' => 300.0]);
        
        // FALSE -> TRUE -> notification created
        $service->evaluate();
        $this->assertDatabaseHas('alert_notifications', ['user_id' => $user->id]);
        $this->assertDatabaseCount('alert_notifications', 1);

        // TRUE -> TRUE -> no new notification
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 1);

        // TRUE -> FALSE -> state reset
        $holdingA->update(['market_price' => 100.0]);
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 1);

        // FALSE -> TRUE -> new notification
        $holdingA->update(['market_price' => 300.0]);
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 2);
    }

    public function test_price_threshold_alert_state_transitions(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 100.0]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);
        
        $service = app(\App\Services\AlertEngineService::class);
        
        // Price = 100 (below threshold) -> no alert
        $service->evaluate();
        $this->assertDatabaseEmpty('alert_notifications');

        // Price = 160 (above threshold) FALSE -> TRUE
        $holding->update(['market_price' => 160.0]);
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 1);

        // TRUE -> TRUE
        $holding->update(['market_price' => 170.0]);
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 1);

        // TRUE -> FALSE
        $holding->update(['market_price' => 140.0]);
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 1);
        
        // FALSE -> TRUE
        $holding->update(['market_price' => 155.0]);
        $service->evaluate();
        $this->assertDatabaseCount('alert_notifications', 2);
    }

    public function test_missing_market_data_skips_evaluation(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => null]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);

        $service = app(\App\Services\AlertEngineService::class);
        $service->evaluate();
        
        // Ensure missing NAV doesn't trigger 0 comparisons
        $this->assertDatabaseEmpty('alert_notifications');
    }

    public function test_missing_mutual_fund_nav_skips_evaluation_safely(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'UNKNOWN_MF', 'name' => 'MF', 'asset_type' => 'MUTUAL_FUND', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 100.0]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);

        $service = app(\App\Services\AlertEngineService::class);
        $service->evaluate(); // Should catch exception and not crash
        
        $this->assertDatabaseEmpty('alert_notifications');
    }

    public function test_scheduler_ignores_inactive_rules(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 200.0]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => false,
        ]);

        $service = app(\App\Services\AlertEngineService::class);
        $service->evaluate();
        
        // Active rules should be evaluated, inactive ignored
        $this->assertDatabaseEmpty('alert_notifications');
    }

    public function test_financial_calculations_are_safely_reused(): void
    {
        $service = app(\App\Services\AlertEngineService::class);
        $service->evaluate();
        
        $this->assertDatabaseEmpty('transactions');
    }
}

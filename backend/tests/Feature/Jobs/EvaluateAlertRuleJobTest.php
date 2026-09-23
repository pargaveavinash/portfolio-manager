<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EvaluateAlertRuleJob;
use App\Models\AlertRule;
use App\Models\AlertNotification;
use App\Models\Holding;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\AlertEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class EvaluateAlertRuleJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_accepts_alert_rule_id_and_evaluates_rule()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 160.0]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);

        $job = new EvaluateAlertRuleJob($rule->id);
        
        // Ensure state is updated correctly by exactly-once atomic evaluation
        $job->handle();

        $this->assertDatabaseCount('alert_notifications', 1);
        $this->assertTrue($rule->fresh()->last_evaluated_state);
    }

    public function test_job_state_transition_prevents_duplicate_notifications()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => 160.0]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);

        $job1 = new EvaluateAlertRuleJob($rule->id);
        $job2 = new EvaluateAlertRuleJob($rule->id); // Simulate concurrent worker
        
        $job1->handle();
        $job2->handle(); // Second run shouldn't create a notification because rule is now evaluated to true

        $this->assertDatabaseCount('alert_notifications', 1);
    }

    public function test_job_safely_handles_missing_market_data()
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id, 'name' => 'Test']);
        // Missing market price
        $holding = Holding::forceCreate(['portfolio_id' => $portfolio->id, 'symbol' => 'AAPL', 'name' => 'Apple', 'asset_type' => 'STOCK', 'quantity' => 10, 'average_price' => 100.0, 'market_price' => null]);
        
        $rule = AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'holding',
            'reference_id' => $holding->id,
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);

        $job = new EvaluateAlertRuleJob($rule->id);
        $job->handle(); 
        
        $this->assertDatabaseEmpty('alert_notifications');
    }

    public function test_job_has_approved_operational_policies()
    {
        $job = new EvaluateAlertRuleJob(1);

        $this->assertEquals(2, $job->tries);
        $this->assertEquals(30, $job->timeout);
        $this->assertEquals(10, $job->backoff());
    }
}

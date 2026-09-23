<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluateAlertsCommandTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_evaluate_alerts_command_dispatches_jobs_for_active_rules(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = \App\Models\User::factory()->create();
        
        $activeRule = \App\Models\AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_ABOVE',
            'reference_type' => 'symbol',
            'reference_id' => 'AAPL',
            'threshold_value' => 150.0,
            'is_active' => true,
        ]);

        $inactiveRule = \App\Models\AlertRule::forceCreate([
            'user_id' => $user->id,
            'type' => 'PRICE_BELOW',
            'reference_type' => 'symbol',
            'reference_id' => 'GOOG',
            'threshold_value' => 100.0,
            'is_active' => false,
        ]);

        $exitCode = Artisan::call('alerts:evaluate');
        
        $this->assertEquals(0, $exitCode);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\EvaluateAlertRuleJob::class, function ($job) use ($activeRule) {
            return $job->alertRuleId === $activeRule->id;
        });

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\EvaluateAlertRuleJob::class, function ($job) use ($inactiveRule) {
            return $job->alertRuleId === $inactiveRule->id;
        });
    }
}

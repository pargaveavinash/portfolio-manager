<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluateAlertsCommandTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_evaluate_alerts_command_runs_successfully(): void
    {
        // Missing implementation means this command doesn't exist yet, so artisan will throw exception
        $exitCode = Artisan::call('alerts:evaluate');
        
        $this->assertEquals(0, $exitCode);
    }
}

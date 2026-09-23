<?php

namespace App\Jobs;

use App\Models\AlertRule;
use App\Services\AlertEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAlertRuleJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 30;
    
    public int $alertRuleId;

    public function __construct(int $alertRuleId)
    {
        $this->alertRuleId = $alertRuleId;
    }

    public function backoff(): int
    {
        return 10;
    }

    public function uniqueId(): string
    {
        return (string) $this->alertRuleId;
    }

    public function handle(): void
    {
        $rule = AlertRule::find($this->alertRuleId);
        if (!$rule || !$rule->is_active) {
            return;
        }

        $engine = app(\App\Services\AlertEngineService::class);
        $engine->evaluateRule($rule);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EvaluateAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:evaluate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate all active alert rules and generate notifications if conditions are met.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting alert evaluation...');
        
        $rules = \App\Models\AlertRule::where('is_active', true)->get();
        foreach ($rules as $rule) {
            \App\Jobs\EvaluateAlertRuleJob::dispatch($rule->id);
        }
        
        $this->info('Alert evaluation completed.');
        
        return 0;
    }
}

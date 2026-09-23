<?php

namespace App\Console\Commands;

use App\Services\AlertEngineService;
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
    public function handle(AlertEngineService $engine): int
    {
        $this->info('Starting alert evaluation...');
        
        $engine->evaluate();
        
        $this->info('Alert evaluation completed.');
        
        return 0;
    }
}

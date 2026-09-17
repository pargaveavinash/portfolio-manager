<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SnapshotSchedulerTest extends TestCase
{
    public function test_scheduler_is_registered_and_configured_for_daily_execution()
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = collect($schedule->events())->filter(function (Event $event) {
            return str_contains($event->command, 'portfolio:snapshot-backfill');
        });

        $this->assertCount(1, $events, 'The snapshot backfill command is not scheduled.');

        /** @var Event $event */
        $event = $events->first();
        
        $this->assertSame('0 2 * * *', $event->expression, 'The command should run daily at 2:00 AM.');
        $this->assertSame('Asia/Kolkata', $event->timezone, 'The command should run in the Asia/Kolkata timezone.');

    }

    public function test_scheduler_invokes_command_with_yesterdays_date_in_asia_kolkata()
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $event = collect($schedule->events())->first(function (Event $event) {
            return str_contains($event->command, 'portfolio:snapshot-backfill');
        });

        $yesterday = Carbon::yesterday('Asia/Kolkata')->toDateString();
        
        $this->assertTrue(
            str_contains($event->command, "--end-date='$yesterday'") || 
            str_contains($event->command, "--end-date=$yesterday") || 
            str_contains($event->command, "'--end-date=$yesterday'"),
            "The command should be scheduled with yesterday's date: --end-date=$yesterday. Got: " . $event->command
        );
    }
}

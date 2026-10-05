<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerRegistrationTest extends TestCase
{
    public function test_snapshot_backfill_command_is_configured_correctly()
    {
        $this->assertCommandConfiguration(
            'portfolio:snapshot-backfill',
            '0 2 * * *',
            'Asia/Kolkata'
        );
    }

    public function test_alerts_evaluate_command_is_configured_correctly()
    {
        $this->assertCommandConfiguration(
            'alerts:evaluate',
            '0 9 * * *',
            'Asia/Kolkata'
        );
    }

    public function test_mutual_funds_sync_navs_command_is_configured_correctly()
    {
        $this->assertCommandConfiguration(
            'mutual-funds:sync-navs',
            '0 23 * * *',
            'Asia/Kolkata'
        );
    }

    private function assertCommandConfiguration(string $commandName, string $expectedExpression, string $expectedTimezone)
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = collect($schedule->events())->filter(function (Event $event) use ($commandName) {
            return str_contains($event->command, $commandName);
        });

        $this->assertCount(1, $events, "The {$commandName} command is not scheduled.");

        /** @var Event $event */
        $event = $events->first();

        $this->assertSame($expectedExpression, $event->expression, "The command {$commandName} should run at {$expectedExpression}.");
        $this->assertSame($expectedTimezone, $event->timezone, "The command {$commandName} should run in the {$expectedTimezone} timezone.");

        $this->assertTrue($event->withoutOverlapping, "The command {$commandName} must use withoutOverlapping().");
        $this->assertTrue($event->onOneServer, "The command {$commandName} must use onOneServer().");
    }
}

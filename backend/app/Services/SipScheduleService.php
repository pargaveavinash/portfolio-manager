<?php

namespace App\Services;

use App\Models\SipPlan;
use Carbon\Carbon;

class SipScheduleService
{
    /**
     * Calculate the next scheduled date based on start date, frequency, and current time.
     * If start_date is in the future, next_scheduled_date is start_date.
     * If start_date is today or in the past, calculate the next future occurrence based on frequency.
     */
    public function calculateNextScheduledDate(
        string $startDate,
        string $frequency,
        string $timezone = 'UTC'
    ): string {
        $now = Carbon::now($timezone)->startOfDay();
        $start = Carbon::parse($startDate, $timezone)->startOfDay();

        if ($start->greaterThan($now)) {
            return $start->toDateString();
        }

        // If it's today or in the past, we need to find the next valid date
        // First occurrence is the start date itself.
        // We iterate occurrences until we find one that is strictly in the future.
        // Or if today is the day, maybe it should be today?
        // Wait, if start_date is today, and it hasn't run yet, next_scheduled_date is today.
        // If it has run today, next_scheduled_date is next frequency.
        // Let's assume if it is created today with start_date today, next_scheduled_date is today.

        $next = $start->copy();

        while ($next->lessThan($now)) {
            switch ($frequency) {
                case 'daily':
                    $next->addDay();
                    break;
                case 'weekly':
                    $next->addWeek();
                    break;
                case 'monthly':
                    $next->addMonthNoOverflow();
                    break;
                case 'quarterly':
                    $next->addMonthsNoOverflow(3);
                    break;
            }
        }

        return $next->toDateString();
    }
}

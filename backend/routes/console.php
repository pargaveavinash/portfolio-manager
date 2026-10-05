<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('portfolio:snapshot-backfill', [
    '--end-date' => Carbon::yesterday('Asia/Kolkata')->toDateString()
])
    ->timezone('Asia/Kolkata')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('mutual-funds:sync-navs')
    ->timezone('Asia/Kolkata')
    ->dailyAt('23:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('alerts:evaluate')
    ->timezone('Asia/Kolkata')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

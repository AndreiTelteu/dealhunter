<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register the crawl deals command for scheduling
Schedule::command('deals:crawl')->hourly()->withoutOverlapping();

// Snapshot average prices of matching, working deals every hour at minute 10
Schedule::command('deals:snapshot-average-prices')->hourlyAt(10)->withoutOverlapping();

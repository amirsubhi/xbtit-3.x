<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire stale peers every 30 minutes (2× the default max_announce interval).
// withoutOverlapping() prevents a slow run from spawning a concurrent one.
Schedule::command('tracker:sanity')->everyThirtyMinutes()->withoutOverlapping();

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Needs one cron line on the server: * * * * * cd /path/to/sofra && php artisan schedule:run
Schedule::command('sofra:backup')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('sofra:subscription-reminders')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('sofra:prune-analytics')->dailyAt('03:30')->withoutOverlapping();

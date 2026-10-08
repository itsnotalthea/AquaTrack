<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// weekly reminders go out Monday 08:00
Schedule::command('reminders:send')->weeklyOn(1, '08:00');

// low stock alert for admins
Schedule::command('inventory:low-stock')->dailyAt('08:15');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bargo:send-daily-digest')->dailyAt('07:30');
Schedule::command('bargo:send-client-payment-reminders')->dailyAt('08:00');
Schedule::command('bargo:backup')->dailyAt('01:15');

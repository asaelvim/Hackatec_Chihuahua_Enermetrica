<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:mark-offline-devices')->everyFifteenMinutes();
Schedule::command('app:summarize-daily-consumption')->dailyAt('00:10');
Schedule::command('app:simulate-live-readings')->everyMinute();

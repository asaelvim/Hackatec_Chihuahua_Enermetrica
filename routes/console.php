<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// El scheduler corre cada 15 minutos (cron "Every 15 minutes" de DreamHost,
// sin necesitar un loop de sleep), así que las tareas aquí deben caer en
// minutos múltiplos de 15 (:00, :15, :30, :45) para que no se salten.
Schedule::command('app:mark-offline-devices')->everyFifteenMinutes();
Schedule::command('app:summarize-daily-consumption')->dailyAt('00:15');

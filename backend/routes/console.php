<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Enviar recordatorios push 10 minutos antes de las citas
// Este comando se ejecuta cada minuto para verificar citas próximas
Schedule::command('citas:recordatorios-push')->everyMinute();

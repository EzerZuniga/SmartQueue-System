<?php

use Illuminate\Support\Facades\Schedule;

// /Tareas Programadas
Schedule::command('app:daily-cleanup')->dailyAt('22:00');
Schedule::command('app:enable-all-services')->dailyAt('07:00');
Schedule::command('app:disable-preferential-service')->dailyAt('15:00');
// Schedule::command('app:check-operational-alerts')->everyMinute(); // Se comento

// TRUCO: Ejecutar el worker de colas desde aquí
Schedule::command('queue:work --stop-when-empty --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

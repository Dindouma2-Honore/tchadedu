<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// "Alerte système" (module Notifications) : rappel quotidien des dossiers
// en brouillon avant clôture d'une session — voir
// App\Console\Commands\SendExamSessionDeadlineReminders pour la logique.
Schedule::command('applications:remind-deadlines')->dailyAt('07:00');

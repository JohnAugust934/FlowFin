<?php

use App\Jobs\RecalculateStreaks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recalcula diariamente a sequência ("streak") de cada usuário, efetivando o reset
// de quem deixou um dia passar sem registrar. Despachado para a fila no banco e
// disparado pelo cron único da hospedagem compartilhada (php artisan schedule:run).
Schedule::job(new RecalculateStreaks)->dailyAt('00:10');

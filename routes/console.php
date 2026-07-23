<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('money-minder:send-daily-summaries')->dailyAt('07:30')->withoutOverlapping();
Schedule::command('money-minder:telegram-companion')->hourly()->withoutOverlapping();
Schedule::command('money-minder:archive-cycles')->dailyAt('00:15')->withoutOverlapping();

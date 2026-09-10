<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:rollover')->dailyAt('00:00');

// Auto-recalculate all target achieved amounts every night from live order/payment/invoice data
Schedule::command('targets:recalculate')->dailyAt('01:00')->withoutOverlapping();

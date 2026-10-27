<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Forge: hidupkan Scheduler pada site (php artisan schedule:run setiap minit).
Schedule::command('dyno:semak-langganan')->dailyAt('09:00')->timezone('Asia/Kuala_Lumpur');

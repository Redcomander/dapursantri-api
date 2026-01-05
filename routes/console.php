<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup of old bukti files (runs daily at 2 AM Jakarta time)
Schedule::command('bukti:cleanup')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->appendOutputTo(storage_path('logs/bukti-cleanup.log'));

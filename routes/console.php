<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule backup otomatis setiap hari pukul 00:00 WITA
Schedule::command('backup:run --only-to-disk=backups')->dailyAt('00:00');

// Schedule pembersihan berkas backup lama (> 7 hari) setiap pukul 01:00 WITA
Schedule::command('backup:clean')->dailyAt('01:00');

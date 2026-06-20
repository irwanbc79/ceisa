<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sinkron master data referensi CEISA setiap hari (03:00) agar HS Code, pelabuhan,
// kurs, kemasan, dll tidak kedaluwarsa. No-op bila reference_endpoints belum diisi.
Schedule::command('ceisa:sync-references')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

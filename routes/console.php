<?php

use App\Services\ReportService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('report:daily {--date=}', function (ReportService $svc) {
    $msg = $svc->dailyMessage($this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : today());
    $this->line($msg);
    $this->info($svc->notifyOwner($msg) ? 'Terkirim ke HP owner.' : 'Webhook belum diatur — ringkasan dicatat di log.');
})->purpose('Kirim ringkasan harian Jelly Potter ke HP owner');

Schedule::command('report:daily')->dailyAt('22:00');

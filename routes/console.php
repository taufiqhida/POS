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

/*
 * Buat akun asli (owner / manager / kasir) dengan password & PIN pilihan sendiri.
 * Di VPS: docker compose exec -u www-data app php artisan jp:user
 */
Artisan::command('jp:user', function () {
    $role = \Laravel\Prompts\select('Peran', \App\Models\User::ROLES, default: 'kasir');
    $name = \Laravel\Prompts\text('Nama', required: true);

    $outletId = null;
    if ($role === 'kasir') {
        $outlets = \App\Models\Outlet::orderBy('name')->pluck('name', 'id')->all();
        if (! $outlets) {
            $this->error('Belum ada outlet. Jalankan dulu: php artisan db:seed --class=ProductionSeeder --force');

            return 1;
        }
        $outletId = \Laravel\Prompts\select('Outlet tugas', $outlets);
    }

    $email = $password = null;
    if ($role !== 'kasir') {
        $email = \Laravel\Prompts\text('Email login panel admin', required: true,
            validate: fn ($v) => ! filter_var($v, FILTER_VALIDATE_EMAIL) ? 'Email tidak valid.'
                : (\App\Models\User::where('email', $v)->exists() ? 'Email sudah dipakai.' : null));
        $password = \Laravel\Prompts\password('Password panel admin (min. 8 karakter)', required: true,
            validate: fn ($v) => strlen($v) < 8 ? 'Minimal 8 karakter.' : null);
    }

    $pin = \Laravel\Prompts\password('PIN kasir (4–6 angka)', required: true,
        validate: fn ($v) => preg_match('/^\d{4,6}$/', $v) ? null : 'PIN harus 4–6 angka.');

    \App\Models\User::create([
        'name' => $name, 'role' => $role, 'outlet_id' => $outletId, 'email' => $email, 'password' => $password,
        'pin' => $pin, 'can_change_price' => $role === 'owner', 'is_active' => true,
    ]);
    $this->info("Akun {$name} ({$role}) dibuat.");
})->purpose('Buat akun owner / manager / kasir');

<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Topping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');

        // Di VPS (aaPanel/Nginx → Docker) header X-Forwarded-Proto tidak selalu diteruskan.
        // Bila APP_URL https, paksa semua URL yang dibuat aplikasi memakai https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Setiap perubahan harga dari panel admin dicatat di riwayat audit.
        $log = function (string $field, callable $label) {
            return function (Model $m) use ($field, $label) {
                if ($m->isDirty($field) && $m->exists) {
                    if (Auth::check() && ! Auth::user()->canChangePrice()) {
                        throw new \Illuminate\Auth\Access\AuthorizationException('Tidak punya hak mengubah harga.');
                    }
                    AuditLog::record('price_change', Auth::id(), class_basename($m), $m->getKey(),
                        $label($m).': Rp'.number_format($m->getOriginal($field), 0, ',', '.').' → Rp'.number_format($m->{$field}, 0, ',', '.'));
                }
            };
        };
        Menu::updating($log('base_price', fn ($m) => $m->name));
        MenuVariant::updating($log('price', fn ($m) => $m->menu->name.' '.$m->size));
        Topping::updating($log('price', fn ($m) => 'Topping '.$m->name));
    }
}

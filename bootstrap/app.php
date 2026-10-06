<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['pos.auth' => \App\Http\Middleware\PosAuth::class]);
        // Di VPS aplikasi berada di belakang reverse proxy (Caddy) yang menangani HTTPS.
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('pos.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sesi habis (419): arahkan kembali ke halaman login, bukan layar "Page Expired".
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }
            $to = $request->is('admin*') ? '/admin/login' : route('pos.login');

            return redirect($to)->withErrors(['pin' => 'Sesi sudah habis, silakan masuk lagi.']);
        });
    })->create();

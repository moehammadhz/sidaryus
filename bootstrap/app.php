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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 "Page Expired": token formulir tidak cocok karena sesi habis atau terhapus
        // (misalnya tabel sessions dikosongkan). Kembalikan ke halaman tadi dengan pesan yang jelas.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()->back(302, [], route('login'))
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'kode']))
                ->with('error', 'Sesi halaman sudah berakhir. Silakan ulangi sekali lagi.');
        });
    })->create();
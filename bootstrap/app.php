<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureKoskuAuthenticated;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'kosku.auth' => EnsureKoskuAuthenticated::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            'login',
            'logout',
            'kamar',
            'kamar/*',
            'manajemen-pengguna',
            'manajemen-pengguna/*',
            'penyewa',
            'penyewa/*',
            'pembayaran',
            'pembayaran/*',
            'komplain',
            'komplain/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

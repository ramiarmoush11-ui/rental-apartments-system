<?php

use App\Http\Middleware\CheckNotBanned;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\verifiedAccount;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->alias([
            'role' => CheckRole::class,
            'verifiedAccount' => verifiedAccount::class,
            'notbanned' => CheckNotBanned::class,
              'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->appendToGroup('api', [
            SetLocale::class
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

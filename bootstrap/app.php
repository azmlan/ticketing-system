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
        $middleware->web(prepend: [
            \App\Modules\Shared\Middleware\SessionTimeoutMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Modules\Shared\Middleware\SetLocaleMiddleware::class,
            \App\Modules\Shared\Middleware\SecurityHeadersMiddleware::class,
        ]);
        $middleware->alias([
            'permission' => \App\Modules\Shared\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

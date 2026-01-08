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
        // Agrega el middleware CORS al stack global
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        
        // También puedes configurar CORS para API específicamente
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'api/*' // Excluye rutas API de CSRF
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

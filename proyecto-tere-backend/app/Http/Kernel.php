<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\HandleCors;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        // Otros middlewares...

        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\RegistrarMascota::class,
        \App\Http\Middleware\CheckUserType::class,
        \App\Http\Middleware\AdminMiddleware::class,
    ];

    protected $routeMiddleware = [
        'user.type' => \App\Http\Middleware\CheckUserType::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ];

    protected $middlewareGroups = [
    'web' => [
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
    // ...
];
}

<?php

use App\Http\Middleware\CamelCaseMiddleware;
use App\Http\Middleware\Cors;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleAuthProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // append or prepend middlewares here
        $middleware->append(ForceJsonResponse::class);
        $middleware->append(Cors::class);
        $middleware->append(CamelCaseMiddleware::class);

        // register all middleware alias here
        $middleware->alias([
            'json.response' => ForceJsonResponse::class,
            'cors' => Cors::class,
            'scopes' => CheckScopes::class,
            'scope' => CheckForAnyScope::class,
            'auth.provider' => HandleAuthProvider::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

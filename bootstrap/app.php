<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PerformanceMonitoring;
use App\Http\Middleware\SecurityMonitoring;
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
        $middleware->trustProxies(at: '*');

        // Add performance monitoring middleware to web routes
        $middleware->web(append: [
            PerformanceMonitoring::class,
            SecurityMonitoring::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

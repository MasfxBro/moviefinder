<?php

use App\Http\Middleware\EnsureUserNotSuspended;
use App\Http\Middleware\EnsureVerifiedForAi;
use App\Http\Middleware\HideAdminFromPublic;
use App\Http\Middleware\SetRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestForgery(except: ['billing/webhook']);
        $middleware->prepend(SetRequestId::class);
        $middleware->appendToGroup('web', [
            AuthenticateSession::class,
            EnsureUserNotSuspended::class,
        ]);
        $middleware->alias([
            'ai.verified' => EnsureVerifiedForAi::class,
            'hide-admin' => HideAdminFromPublic::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->context(function () {
            if (! app()->bound('request_id')) {
                app()->instance('request_id', Str::lower(Str::random(8)));
            }

            return [
                'request_id' => app('request_id'),
            ];
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

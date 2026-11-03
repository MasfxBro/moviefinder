<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('request_id')) {
            $requestId = Str::lower(Str::random(8));
            app()->instance('request_id', $requestId);
            $request->attributes->set('request_id', $requestId);
        }

        return $next($request);
    }
}

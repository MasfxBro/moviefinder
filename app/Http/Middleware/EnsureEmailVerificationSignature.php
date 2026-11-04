<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerificationSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If not authenticated, redirect to login first
        if (! $request->user()) {
            // Store the verification URL to continue after login
            session(['url.intended' => $request->fullUrl()]);

            return redirect()->route('login')->with('message', 'Please log in to verify your email address.');
        }

        // Check if signature is valid
        if (! URL::hasValidSignature($request)) {
            return redirect()->route('verification.notice')->with('invalid', true);
        }

        return $next($request);
    }
}

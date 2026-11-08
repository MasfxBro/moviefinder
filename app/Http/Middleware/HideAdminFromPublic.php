<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HideAdminFromPublic
{
    public function handle(Request $request, Closure $next): Response
    {
        $isVerifiedAdmin = $request->user('admin')?->isAdmin()
            && $request->session()->get('admin_2fa_verified') === true;
        $isCompletingTwoFactor = $request->user('admin')?->isAdmin()
            && $request->session()->get('admin_pending_2fa') === true
            && $request->routeIs('admin.2fa', 'admin.2fa.verify');

        abort_unless($isVerifiedAdmin || $isCompletingTwoFactor, 404);

        $response = $next($request);

        return $response;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminNewIpLogin;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AdminLoginController extends Controller
{
    public function create(): Response
    {
        return response()->view('admin.login')->withHeaders(['X-Robots-Tag' => 'noindex']);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', Str::lower($data['email']))->where('role', Role::Admin)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit('admin-login:'.$request->ip(), 60);
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        RateLimiter::clear('admin-login:'.$request->ip());

        $allowedIps = array_filter(array_map('trim', explode(',', (string) config('admin.allowed_ips'))));
        abort_if($allowedIps && ! in_array($request->ip(), $allowedIps, true), 404);

        Auth::guard('admin')->login($user);
        $request->session()->regenerate();
        $request->session()->put('admin_pending_2fa', true);
        $request->session()->forget('admin_2fa_verified');

        $request->session()->put(
            'admin_new_ip',
            $user->admin_last_ip_hash !== hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        );
        $auditLogger->log($request, 'admin.login');

        return redirect()->route('admin.2fa');
    }

    public function verify(Request $request): RedirectResponse
    {
        abort_unless($request->user('admin')?->isAdmin() && $request->session()->get('admin_pending_2fa'), 404);
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = $request->user('admin');
        $valid = app(Google2FA::class)->verifyKey($user->admin_totp_secret, $data['code']);
        $codes = $user->admin_recovery_codes ?? [];

        if (! $valid) {
            foreach ($codes as $index => $hash) {
                if (Hash::check($data['code'], $hash)) {
                    unset($codes[$index]);
                    $user->admin_recovery_codes = array_values($codes);
                    $user->save();
                    $valid = true;
                    break;
                }
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Invalid authentication code.']);
        }

        $user->admin_last_ip_hash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $user->save();
        if ($request->session()->pull('admin_new_ip')) {
            $user->notify(new AdminNewIpLogin);
        }
        $request->session()->forget('admin_pending_2fa');
        $request->session()->put('admin_2fa_verified', true);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}

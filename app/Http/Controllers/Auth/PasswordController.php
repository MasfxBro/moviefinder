<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $newPassword = $request->validated('password');
        $request->user()->update([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ]);

        auth()->logoutOtherDevices($newPassword);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->getKey())
                ->delete();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'password-updated');
        }

        return back()->with('status', 'password-updated');
    }
}

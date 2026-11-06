<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestPasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /** Handle a validated password reset link request. */
    public function store(RequestPasswordResetLinkRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $status = Password::sendResetLink(['email' => $validated['email']]);

        return $status === Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput(['email' => $validated['email']])
                        ->withErrors(['email' => __($status)]);
    }
}

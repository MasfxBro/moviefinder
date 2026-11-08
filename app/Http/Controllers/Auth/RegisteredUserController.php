<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        if (! app(Settings::class)->get('features.registration_open', true)) {
            return view('auth.register', ['maintenance' => true]);
        }

        return view('auth.register');
    }

    /** Handle a validated registration request. */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::create([
            'name' => trim($validated['name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'age_confirmed_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }
}

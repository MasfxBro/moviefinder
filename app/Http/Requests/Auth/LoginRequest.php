<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower($this->string('email')->trim()->toString());
        $user = User::where('email', $email)->first();

        if ($user?->suspended_at !== null || ! Auth::attempt([
            'email' => $email,
            'password' => $this->string('password')->toString(),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->applyProgressiveDelay();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Block admin accounts from public login
        if (Auth::user()->isAdmin()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            $this->applyProgressiveDelay();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->delayKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $delayKey = $this->delayKey();

        if (RateLimiter::tooManyAttempts($delayKey, 1)) {
            $seconds = RateLimiter::availableIn($delayKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $attempts = RateLimiter::attempts($key);
        $delay = match (true) {
            $attempts >= 4 => 30,
            $attempts >= 3 => 10,
            $attempts >= 2 => 3,
            default => 0,
        };

        if ($delay > 0) {
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $delay,
                    'minutes' => ceil($delay / 60),
                ]),
            ]);
        }
    }

    private function delayKey(): string
    {
        return 'login-delay:'.$this->throttleKey();
    }

    private function applyProgressiveDelay(): void
    {
        $attempts = RateLimiter::attempts($this->throttleKey());
        $decaySeconds = match (true) {
            $attempts >= 5 => 60,
            $attempts === 4 => 30,
            $attempts === 3 => 10,
            $attempts === 2 => 3,
            default => 0,
        };

        if ($decaySeconds > 0) {
            RateLimiter::hit($this->delayKey(), $decaySeconds);
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

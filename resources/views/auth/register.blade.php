<x-guest-layout>
    @if ($maintenance ?? false)
        <x-under-maintenance feature="Registration" />
    @else
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Age Confirmation -->
        <div class="mt-4">
            <label for="age_confirmed" class="inline-flex items-center">
                <input id="age_confirmed" type="checkbox" name="age_confirmed" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" required {{ old('age_confirmed') ? 'checked' : '' }}>
                <span class="ms-2 text-sm text-gray-600">I am 18 or older</span>
            </label>
            <x-input-error :messages="$errors->get('age_confirmed')" class="mt-2" />
        </div>

        <!-- Terms and Privacy -->
        <div class="mt-4">
            <label for="terms_accepted" class="inline-flex items-center">
                <input id="terms_accepted" type="checkbox" name="terms_accepted" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" required {{ old('terms_accepted') ? 'checked' : '' }}>
                <span class="ms-2 text-sm text-gray-600">
                    I agree to the <a href="{{ route('terms') }}" class="underline text-indigo-600 hover:text-indigo-900" target="_blank">Terms of Service</a> and <a href="{{ route('privacy') }}" class="underline text-indigo-600 hover:text-indigo-900" target="_blank">Privacy Policy</a>
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms_accepted')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
    @endif
</x-guest-layout>

<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Please verify your email address. We have sent a link to <strong>{{ auth()->user()->email }}</strong>.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            A new verification link has been sent to your email address.
        </div>
    @endif

    @if (session('verified'))
        <div class="mb-4 font-medium text-sm text-green-600">
            Your email address has been verified.
        </div>
    @endif

    @if (session('expired'))
        <div class="mb-4 font-medium text-sm text-red-600">
            This verification link has expired. Please request a new one.
        </div>
    @endif

    @if (session('invalid'))
        <div class="mb-4 font-medium text-sm text-red-600">
            This verification link is invalid. Please request a new one.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                Resend verification email
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Log out
            </button>
        </form>
    </div>
</x-guest-layout>

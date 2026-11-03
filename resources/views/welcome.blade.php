<x-app-layout>
    <div class="py-12">
        <x-empty-state
            title="Movie Finder"
            description="Describe a scene. We find the film."
        >
            <x-slot:action>
                @guest
                    <div class="flex items-center justify-center gap-4">
                        <x-button variant="primary" onclick="window.location.href='{{ route('register') }}'">
                            Create account
                        </x-button>
                        <x-button variant="secondary" onclick="window.location.href='{{ route('login') }}'">
                            Log in
                        </x-button>
                    </div>
                @endguest
            </x-slot:action>
        </x-empty-state>
    </div>
</x-app-layout>

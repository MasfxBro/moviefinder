<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-empty-state
                title="Your catalog is empty"
                description="Describe a scene in the AI Chat to find your first title."
            >
                <x-slot:action>
                    {{-- AI Chat button will go here or be in the navbar --}}
                </x-slot:action>
            </x-empty-state>
        </div>
    </div>
</x-app-layout>

<div class="relative py-16 text-center lg:py-32 overflow-hidden">
    <div class="relative z-10 mx-auto max-w-4xl px-4">
        <h1 class="text-4xl font-bold tracking-tight text-text sm:text-7xl leading-tight">
            Describe a scene.<br>
            <span class="text-accent">We find the film.</span>
        </h1>
        <p class="mx-auto mt-8 max-w-2xl text-lg text-muted leading-relaxed">
            Stop searching by titles. Find movies and series by what actually happens in them, powered by AI that understands cinema.
        </p>
        <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
            <x-button variant="primary" size="lg" onclick="window.location.href='{{ route('register') }}'">
                Create account
            </x-button>
            <x-button variant="secondary" size="lg" onclick="window.location.href='{{ route('login') }}'">
                Log in
            </x-button>
        </div>
    </div>
    
    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-accent/20 rounded-full blur-[120px]"></div>
    </div>
</div>

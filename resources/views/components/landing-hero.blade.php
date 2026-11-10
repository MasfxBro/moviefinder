<div class="relative py-16 text-center lg:py-32 overflow-hidden">
    <div class="relative z-10 mx-auto max-w-4xl px-4">
        <h1 class="text-4xl font-bold tracking-tight text-text sm:text-7xl leading-tight">
            Describe a scene.<br>
            <span class="text-accent">We find the film.</span>
        </h1>
        <p class="mx-auto mt-8 max-w-2xl text-lg text-muted leading-relaxed">
            Stop searching by titles. Find movies and series by what actually happens in them, powered by AI that understands cinema.
        </p>
        <div class="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a href="{{ route('register') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-medium text-text transition-colors duration-150 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg">
                Create account
            </a>
            <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-card px-4 py-2 text-sm font-medium text-text transition-colors duration-150 hover:bg-elevated focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg">
                Log in
            </a>
        </div>
    </div>
    
    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-accent/20 rounded-full blur-[120px]"></div>
    </div>
</div>

<footer class="bg-surface border-t border-border mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-muted">
        <div>
            &copy; {{ date('Y') }} {{ config('app.name', 'MovieFinder') }}. All rights reserved.
        </div>
        <div class="flex items-center gap-6">
            <a href="{{ url('/terms') }}" class="hover:text-text transition-colors">
                Terms of Service
            </a>
            <a href="{{ url('/privacy') }}" class="hover:text-text transition-colors">
                Privacy Policy
            </a>
        </div>
        <div>
            Film data provided by TMDB.
        </div>
    </div>
</footer>

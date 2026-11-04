<footer class="bg-surface border-t border-border mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-muted">
        <div>
            &copy; {{ date('Y') }} {{ config('app.name', 'MovieFinder') }}. All rights reserved.
        </div>
        <div class="flex items-center flex-wrap gap-4 justify-center">
            <a href="{{ url('/terms') }}" class="hover:text-text transition-colors">Terms of Service</a>
            <a href="{{ url('/privacy') }}" class="hover:text-text transition-colors">Privacy Policy</a>
            <a href="{{ url('/copyright') }}" class="hover:text-text transition-colors">Copyright</a>
            <a href="{{ url('/about') }}" class="hover:text-text transition-colors">About</a>
            <a href="https://www.youtube.com/t/terms" target="_blank" rel="noopener" class="hover:text-text transition-colors">YouTube Terms</a>
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="hover:text-text transition-colors">Google Privacy</a>
        </div>
        <div class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
            <span>This product uses the TMDB API but is not endorsed or certified by TMDB.</span>
            <span class="hidden md:inline text-faint">|</span>
            <span>Where-to-watch data provided by JustWatch.</span>
        </div>
    </div>
</footer>

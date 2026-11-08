@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-danger">Delete Title Confirmation</h1>
            <p class="text-sm text-muted">Review dependencies before permanently removing this title</p>
        </div>
        <a href="{{ route('admin.titles.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
    </div>

    <div class="p-6 rounded-2xl bg-card border border-danger/30 space-y-6">
        <div class="flex items-start gap-4">
            <div class="w-16 h-24 rounded-lg overflow-hidden bg-surface border border-border flex-shrink-0">
                @if ($title->poster_path)
                    <img src="{{ $title->poster_url }}" alt="" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-xs text-muted">No poster</div>
                @endif
            </div>
            <div>
                <h2 class="text-lg font-bold text-text">{{ $title->title }}</h2>
                <div class="text-xs text-muted font-mono mt-0.5">{{ $title->slug }}</div>
                <div class="mt-2 flex gap-2">
                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-elevated text-muted uppercase">{{ $title->media_type }}</span>
                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $title->source === 'tmdb' ? 'bg-accent/15 text-accent' : 'bg-pro/15 text-pro' }}">{{ strtoupper($title->source) }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted">Dependency Summary</h3>
            <p class="text-xs text-muted">Deleting this title will trigger the following cascading changes:</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-surface border border-border text-center">
                    <span class="text-xl font-bold text-text block">{{ $dependencies['watchlists'] }}</span>
                    <span class="text-xs text-muted">Watchlist Entries</span>
                </div>
                <div class="p-3 rounded-xl bg-surface border border-border text-center">
                    <span class="text-xl font-bold text-text block">{{ $dependencies['clip_links'] }}</span>
                    <span class="text-xs text-muted">Clip Links</span>
                </div>
                <div class="p-3 rounded-xl bg-surface border border-border text-center">
                    <span class="text-xl font-bold text-text block">{{ $dependencies['title_requests'] }}</span>
                    <span class="text-xs text-muted">Title Requests</span>
                </div>
                <div class="p-3 rounded-xl bg-surface border border-border text-center">
                    <span class="text-xl font-bold text-text block">{{ $dependencies['ai_usage_logs'] }}</span>
                    <span class="text-xs text-muted">AI Logs (Nulled)</span>
                </div>
                <div class="p-3 rounded-xl bg-surface border border-border text-center">
                    <span class="text-xl font-bold text-text block">{{ $dependencies['genres'] }}</span>
                    <span class="text-xs text-muted">Genres Attached</span>
                </div>
                <div class="p-3 rounded-xl bg-surface border border-border text-center">
                    <span class="text-xl font-bold text-text block">{{ $dependencies['people'] }}</span>
                    <span class="text-xs text-muted">People Attached</span>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-danger/10 border border-danger/20 text-xs text-danger">
            <strong>Warning:</strong> This action is irreversible. The local poster (if manual) and relations will be removed, and catalog cache will be cleared immediately.
        </div>

        <form method="POST" action="{{ route('admin.titles.destroy', $title) }}" class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            @csrf
            @method('DELETE')
            <a href="{{ route('admin.titles.index') }}" class="px-4 py-2 text-sm text-muted hover:text-text">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-danger text-white font-medium text-sm rounded-xl hover:bg-danger/90 transition-colors">
                Confirm &amp; Delete Permanently
            </button>
        </form>
    </div>
</div>
@endsection

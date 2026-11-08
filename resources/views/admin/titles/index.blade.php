@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Titles Management</h1>
            <p class="text-sm text-muted">Manage catalog titles, import from TMDB, and create manual entries</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.titles.create') }}" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">
                + New Manual Title
            </a>
        </div>
    </div>

    {{-- Import Tools Accordion / Sections --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Single TMDB Import --}}
        <div class="p-5 rounded-2xl bg-card border border-border">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-muted mb-3">Import from TMDB</h2>
            <form method="POST" action="{{ route('admin.titles.import') }}" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <input type="number" name="tmdb_id" required min="1" placeholder="TMDB ID (e.g. 550)" class="flex-1 bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                <select name="media_type" required class="bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                    <option value="movie">Movie</option>
                    <option value="tv">TV Show</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">
                    Import
                </button>
            </form>
        </div>

        {{-- Bulk TMDB Import --}}
        <div class="p-5 rounded-2xl bg-card border border-border">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-muted mb-3">Bulk Import by IDs</h2>
            <form method="POST" action="{{ route('admin.titles.bulk_import') }}" class="space-y-2">
                @csrf
                <div class="flex gap-2">
                    <textarea name="ids" rows="2" required placeholder="IDs separated by comma or newline (e.g. 550, 680, 27205 or 550:movie, 1399:tv)" class="flex-1 bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent"></textarea>
                    <div class="flex flex-col gap-2">
                        <select name="media_type" class="bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                            <option value="movie">Movie</option>
                            <option value="tv">TV Show</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">
                            Bulk Import
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="p-4 rounded-2xl bg-card border border-border">
        <form method="GET" action="{{ route('admin.titles.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title, slug, or TMDB ID..." class="flex-1 min-w-[200px] bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">

            <select name="source" class="bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                <option value="">All Sources</option>
                <option value="tmdb" @selected(($filters['source'] ?? '') === 'tmdb')>TMDB</option>
                <option value="manual" @selected(($filters['source'] ?? '') === 'manual')>Manual</option>
            </select>

            <select name="media_type" class="bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                <option value="">All Types</option>
                <option value="movie" @selected(($filters['media_type'] ?? '') === 'movie')>Movie</option>
                <option value="tv" @selected(($filters['media_type'] ?? '') === 'tv')>TV Show</option>
            </select>

            <select name="hidden" class="bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                <option value="">All Statuses</option>
                <option value="0" @selected(($filters['hidden'] ?? '') === '0')>Visible</option>
                <option value="1" @selected(($filters['hidden'] ?? '') === '1')>Hidden</option>
            </select>

            <select name="stale" class="bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                <option value="">All Freshness</option>
                <option value="1" @selected(($filters['stale'] ?? '') === '1')>Stale (&gt; 150 days)</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Filter</button>
            @if (!empty(array_filter($filters)))
                <a href="{{ route('admin.titles.index') }}" class="px-3 py-2 text-sm text-muted hover:text-text">Reset</a>
            @endif
        </form>
    </div>

    {{-- Titles Table --}}
    <div class="rounded-2xl bg-card border border-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-text">
                <thead class="bg-surface border-b border-border text-xs uppercase tracking-wider text-muted font-semibold">
                    <tr>
                        <th class="p-4 w-14">Poster</th>
                        <th class="p-4">Title &amp; Details</th>
                        <th class="p-4">Source</th>
                        <th class="p-4">Locked Fields</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Last Synced</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($titles as $title)
                        <tr class="hover:bg-surface/50 transition-colors">
                            <td class="p-4">
                                <div class="w-10 h-14 rounded overflow-hidden bg-surface border border-border flex-shrink-0">
                                    @if ($title->poster_path)
                                        <img src="{{ $title->poster_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-[10px] text-muted">No img</div>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="font-semibold text-text flex items-center gap-2">
                                    <span>{{ $title->title }}</span>
                                    @if ($title->release_year)
                                        <span class="text-xs text-muted">({{ $title->release_year }})</span>
                                    @endif
                                    <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-elevated text-muted">
                                        {{ $title->media_type }}
                                    </span>
                                </div>
                                <div class="text-xs text-muted font-mono mt-0.5">{{ $title->slug }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $title->source === 'tmdb' ? 'bg-accent/15 text-accent' : 'bg-pro/15 text-pro' }}">
                                    {{ strtoupper($title->source) }}
                                    @if ($title->tmdb_id)
                                        <span class="text-[10px] font-mono">#{{ $title->tmdb_id }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="p-4">
                                @if (!empty($title->locked_fields))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($title->locked_fields as $field)
                                            <span class="px-1.5 py-0.5 rounded text-[11px] bg-elevated text-muted font-mono">{{ $field }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-muted">-</span>
                                @endif
                            </td>
                            <td class="p-4">
                                @if ($title->is_hidden)
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-danger/15 text-danger">Hidden</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-success/15 text-success">Visible</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-muted">
                                @if ($title->synced_at)
                                    <span>{{ $title->synced_at->diffForHumans() }}</span>
                                    @if ($title->synced_at->lt(now()->subDays(150)))
                                        <span class="text-danger block text-[10px] font-semibold">STALE</span>
                                    @endif
                                @else
                                    <span>Manual</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.titles.edit', $title) }}" class="px-2.5 py-1 rounded-lg text-xs bg-elevated hover:bg-border text-text font-medium transition-colors">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.titles.toggle_hide', $title) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $title->is_hidden ? 'bg-success/20 text-success hover:bg-success/30' : 'bg-danger/20 text-danger hover:bg-danger/30' }}">
                                            {{ $title->is_hidden ? 'Unhide' : 'Hide' }}
                                        </button>
                                    </form>
                                    @if ($title->source === 'tmdb')
                                        <form method="POST" action="{{ route('admin.titles.refresh', $title) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs bg-elevated hover:bg-border text-text font-medium transition-colors">
                                                Refresh
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.titles.delete_confirm', $title) }}" class="px-2.5 py-1 rounded-lg text-xs text-danger hover:bg-danger/15 font-medium transition-colors">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-muted">
                                No titles found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($titles->hasPages())
            <div class="p-4 border-t border-border">
                {{ $titles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

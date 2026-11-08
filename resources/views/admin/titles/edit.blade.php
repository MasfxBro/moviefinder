@extends('admin.layout')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Edit Title</h1>
            <p class="text-sm text-muted">Editing: {{ $title->title }} ({{ strtoupper($title->source) }})</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.titles.delete_confirm', $title) }}" class="text-xs text-danger hover:underline">Delete title</a>
            <a href="{{ route('admin.titles.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.titles.update', $title) }}" enctype="multipart/form-data" class="space-y-6 p-6 rounded-2xl bg-card border border-border">
        @csrf
        @method('PUT')

        {{-- Locked Fields Notice --}}
        <div class="p-4 rounded-xl bg-surface border border-border space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted">Locked Fields (Protected from TMDB Refresh)</span>
                <span class="text-[11px] text-muted">Any edited field is automatically locked</span>
            </div>
            @if (!empty($title->locked_fields))
                <div class="flex flex-wrap gap-2 pt-1">
                    @foreach ($title->locked_fields as $field)
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono bg-elevated text-accent cursor-pointer border border-accent/20">
                            <input type="checkbox" name="locked_fields[]" value="{{ $field }}" checked class="rounded border-border text-accent focus:ring-0">
                            <span>{{ $field }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-muted">No fields currently locked. Future TMDB refresh jobs will update all fields.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Title *</label>
                <input type="text" name="title" value="{{ old('title', $title->title) }}" required class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Original Title</label>
                <input type="text" name="original_title" value="{{ old('original_title', $title->original_title) }}" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Release Year</label>
                <input type="number" name="release_year" value="{{ old('release_year', $title->release_year) }}" min="1880" max="2100" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Runtime (Minutes)</label>
                <input type="number" name="runtime_minutes" value="{{ old('runtime_minutes', $title->runtime_minutes) }}" min="1" max="1000" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Language Code</label>
                <input type="text" name="original_language" value="{{ old('original_language', $title->original_language) }}" maxlength="10" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>
        </div>

        @if ($title->media_type === 'tv')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-muted">Seasons Count</label>
                    <input type="number" name="seasons_count" value="{{ old('seasons_count', $title->seasons_count) }}" min="1" max="100" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-muted">Episodes Count</label>
                    <input type="number" name="episodes_count" value="{{ old('episodes_count', $title->episodes_count) }}" min="1" max="10000" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                </div>
            </div>
        @endif

        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted">Tagline</label>
            <input type="text" name="tagline" value="{{ old('tagline', $title->tagline) }}" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
        </div>

        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted">Overview / Synopsis</label>
            <textarea name="overview" rows="4" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">{{ old('overview', $title->overview) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Trailer YouTube Key</label>
                <input type="text" name="trailer_youtube_key" value="{{ old('trailer_youtube_key', $title->trailer_youtube_key) }}" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Visibility Status</label>
                <select name="is_hidden" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                    <option value="0" @selected(!old('is_hidden', $title->is_hidden))>Visible</option>
                    <option value="1" @selected(old('is_hidden', $title->is_hidden))>Hidden</option>
                </select>
            </div>
        </div>

        {{-- Poster Preview & Upload --}}
        <div class="p-4 rounded-xl bg-surface border border-border space-y-3">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted block">Poster</label>
            <div class="flex items-start gap-4">
                <div class="w-20 h-28 rounded-lg overflow-hidden bg-card border border-border flex-shrink-0">
                    @if ($title->poster_path)
                        <img src="{{ $title->poster_url }}" alt="" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-xs text-muted">No poster</div>
                    @endif
                </div>
                <div class="space-y-2 flex-1">
                    <p class="text-xs text-muted">Replace poster: Upload JPEG, PNG, or WebP (max 2 MB). Re-encoded to WebP max 600 px and 300 KB.</p>
                    <input type="file" name="poster" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-elevated file:text-text hover:file:bg-border cursor-pointer">
                    <div class="text-[11px] font-mono text-muted">Current: {{ $title->poster_path ?? 'None' }}</div>
                </div>
            </div>
        </div>

        {{-- Genres --}}
        <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted block">Genres</label>
            @php
                $currentGenreIds = old('genres', $title->genres->pluck('id')->all());
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach ($genres as $genre)
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-surface border border-border text-xs text-text cursor-pointer hover:bg-elevated/50">
                        <input type="checkbox" name="genres[]" value="{{ $genre->id }}" @checked(in_array($genre->id, $currentGenreIds)) class="rounded border-border text-accent focus:ring-0">
                        <span>{{ $genre->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <a href="{{ route('admin.titles.index') }}" class="px-4 py-2 text-sm text-muted hover:text-text">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

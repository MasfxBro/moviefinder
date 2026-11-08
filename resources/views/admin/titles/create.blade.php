@extends('admin.layout')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Create Manual Title</h1>
            <p class="text-sm text-muted">Add a title not available on TMDB with custom poster image</p>
        </div>
        <a href="{{ route('admin.titles.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
    </div>

    <form method="POST" action="{{ route('admin.titles.store') }}" enctype="multipart/form-data" class="space-y-6 p-6 rounded-2xl bg-card border border-border">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Original Title</label>
                <input type="text" name="original_title" value="{{ old('original_title') }}" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Media Type *</label>
                <select name="media_type" required class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
                    <option value="movie" @selected(old('media_type') === 'movie')>Movie</option>
                    <option value="tv" @selected(old('media_type') === 'tv')>TV Show</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Release Year</label>
                <input type="number" name="release_year" value="{{ old('release_year') }}" min="1880" max="2100" placeholder="2024" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Runtime (Minutes)</label>
                <input type="number" name="runtime_minutes" value="{{ old('runtime_minutes') }}" min="1" max="1000" placeholder="120" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Seasons Count (TV)</label>
                <input type="number" name="seasons_count" value="{{ old('seasons_count') }}" min="1" max="100" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Episodes Count (TV)</label>
                <input type="number" name="episodes_count" value="{{ old('episodes_count') }}" min="1" max="10000" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-muted">Language Code</label>
                <input type="text" name="original_language" value="{{ old('original_language', 'en') }}" maxlength="10" placeholder="en, id, ja" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
            </div>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted">Tagline</label>
            <input type="text" name="tagline" value="{{ old('tagline') }}" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
        </div>

        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted">Overview / Synopsis</label>
            <textarea name="overview" rows="4" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">{{ old('overview') }}</textarea>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted">Trailer YouTube Key</label>
            <input type="text" name="trailer_youtube_key" value="{{ old('trailer_youtube_key') }}" placeholder="e.g. dQw4w9WgXcQ" class="w-full bg-surface border border-border rounded-xl px-3 py-2 text-sm text-text focus:outline-none focus:border-accent">
        </div>

        {{-- Poster Upload --}}
        <div class="p-4 rounded-xl bg-surface border border-border space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted block">Poster Image (Admin Upload)</label>
            <p class="text-xs text-muted">Max 2 MB input. MIME validated by content, converted with GD to WebP (max 600 px wide, at most 300 KB). Stored locally on disk.</p>
            <input type="file" name="poster" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-elevated file:text-text hover:file:bg-border cursor-pointer">
        </div>

        {{-- Genres --}}
        <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-muted block">Genres</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach ($genres as $genre)
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-surface border border-border text-xs text-text cursor-pointer hover:bg-elevated/50">
                        <input type="checkbox" name="genres[]" value="{{ $genre->id }}" @checked(in_array($genre->id, old('genres', []))) class="rounded border-border text-accent focus:ring-0">
                        <span>{{ $genre->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <a href="{{ route('admin.titles.index') }}" class="px-4 py-2 text-sm text-muted hover:text-text">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">
                Save Title
            </button>
        </div>
    </form>
</div>
@endsection

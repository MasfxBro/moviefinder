@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Create Clip Link</h1>
            <p class="text-sm text-muted">Add a curated or manual clip link for a title</p>
        </div>
        <a href="{{ route('admin.clip_links.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
    </div>

    <form action="{{ route('admin.clip_links.store') }}" method="POST" class="p-6 rounded-2xl bg-card border border-border space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Title</label>
            <select name="title_id" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                <option value="">Select Title...</option>
                @foreach ($titles as $title)
                    <option value="{{ $title->id }}" @selected(old('title_id') == $title->id)>{{ $title->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Video URL (YouTube, etc.)</label>
            <input type="url" name="url" value="{{ old('url') }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Label / Description</label>
            <input type="text" name="label" value="{{ old('label') }}" placeholder="e.g. Official Trailer / Scene preview" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Trust Level</label>
            <select name="trust" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                <option value="official">Official</option>
                <option value="trusted" selected>Trusted</option>
                <option value="unverified">Unverified</option>
            </select>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.clip_links.index') }}" class="px-4 py-2 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Create Clip Link</button>
        </div>
    </form>
</div>
@endsection

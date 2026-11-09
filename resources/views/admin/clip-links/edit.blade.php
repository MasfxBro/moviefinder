@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Edit Clip Link</h1>
            <p class="text-sm text-muted">Modify clip link properties and status</p>
        </div>
        <a href="{{ route('admin.clip_links.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
    </div>

    <form action="{{ route('admin.clip_links.update', $clipLink) }}" method="POST" class="p-6 rounded-2xl bg-card border border-border space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Title</label>
            <select name="title_id" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                @foreach ($titles as $title)
                    <option value="{{ $title->id }}" @selected(old('title_id', $clipLink->title_id) == $title->id)>{{ $title->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Video URL</label>
            <input type="url" name="url" value="{{ old('url', $clipLink->url) }}" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Label / Description</label>
            <input type="text" name="label" value="{{ old('label', $clipLink->label) }}" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Trust Level</label>
                <select name="trust" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                    <option value="official" @selected($clipLink->trust === 'official')>Official</option>
                    <option value="trusted" @selected($clipLink->trust === 'trusted')>Trusted</option>
                    <option value="unverified" @selected($clipLink->trust === 'unverified')>Unverified</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                    <option value="active" @selected($clipLink->status === 'active')>Active</option>
                    <option value="reported" @selected($clipLink->status === 'reported')>Reported</option>
                    <option value="removed" @selected($clipLink->status === 'removed')>Removed</option>
                </select>
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.clip_links.index') }}" class="px-4 py-2 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Update Clip Link</button>
        </div>
    </form>
</div>
@endsection

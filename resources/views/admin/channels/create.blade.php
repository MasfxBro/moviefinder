@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Add Channel to Allow-list</h1>
            <p class="text-sm text-muted">Register channel trust level for clip embedding</p>
        </div>
        <a href="{{ route('admin.channels.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
    </div>

    <form action="{{ route('admin.channels.store') }}" method="POST" class="p-6 rounded-2xl bg-card border border-border space-y-4">
        @csrf

        <input type="hidden" name="platform" value="youtube">

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Channel Name</label>
            <input type="text" name="channel_name" value="{{ old('channel_name') }}" placeholder="e.g. Netflix, Warner Bros. Pictures" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Channel ID (optional)</label>
            <input type="text" name="channel_id" value="{{ old('channel_id') }}" placeholder="e.g. UCX6OQ3DkcsbYNE6H8uQQuVA" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Trust Level</label>
            <select name="trust" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                <option value="official">Official</option>
                <option value="trusted" selected>Trusted</option>
                <option value="blocked">Blocked</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Notes (optional)</label>
            <textarea name="notes" rows="3" placeholder="Reason or attribution details..." class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">{{ old('notes') }}</textarea>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.channels.index') }}" class="px-4 py-2 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Add Channel</button>
        </div>
    </form>
</div>
@endsection

@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Edit Channel</h1>
            <p class="text-sm text-muted">Update channel trust or notes</p>
        </div>
        <a href="{{ route('admin.channels.index') }}" class="text-sm text-muted hover:text-text">Back to list</a>
    </div>

    <form action="{{ route('admin.channels.update', $channel) }}" method="POST" class="p-6 rounded-2xl bg-card border border-border space-y-4">
        @csrf
        @method('PUT')

        <input type="hidden" name="platform" value="{{ $channel->platform }}">

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Channel Name</label>
            <input type="text" name="channel_name" value="{{ old('channel_name', $channel->channel_name) }}" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Channel ID</label>
            <input type="text" name="channel_id" value="{{ old('channel_id', $channel->channel_id) }}" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Trust Level</label>
            <select name="trust" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent" required>
                <option value="official" @selected($channel->trust === 'official')>Official</option>
                <option value="trusted" @selected($channel->trust === 'trusted')>Trusted</option>
                <option value="blocked" @selected($channel->trust === 'blocked')>Blocked</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-muted mb-1">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">{{ old('notes', $channel->notes) }}</textarea>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.channels.index') }}" class="px-4 py-2 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Update Channel</button>
        </div>
    </form>
</div>
@endsection

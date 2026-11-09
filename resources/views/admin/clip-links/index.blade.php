@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Clip Links Management</h1>
            <p class="text-sm text-muted">CRUD, verify now, and bulk remove by channel</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.clip_links.create') }}" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Add Clip Link</a>
        </div>
    </div>

    {{-- Filters & Bulk Remove --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <form method="GET" action="{{ route('admin.clip_links.index') }}" class="p-4 rounded-2xl bg-card border border-border flex flex-wrap gap-3 items-center">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search URL, channel, label..." class="px-3 py-1.5 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent flex-1">
            <select name="status" class="px-3 py-1.5 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
                <option value="">All Statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="reported" @selected(($filters['status'] ?? '') === 'reported')>Reported</option>
                <option value="removed" @selected(($filters['status'] ?? '') === 'removed')>Removed</option>
            </select>
            <select name="trust" class="px-3 py-1.5 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
                <option value="">All Trust</option>
                <option value="official" @selected(($filters['trust'] ?? '') === 'official')>Official</option>
                <option value="trusted" @selected(($filters['trust'] ?? '') === 'trusted')>Trusted</option>
                <option value="unverified" @selected(($filters['trust'] ?? '') === 'unverified')>Unverified</option>
            </select>
            <button type="submit" class="px-4 py-1.5 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">Filter</button>
        </form>

        <form method="POST" action="{{ route('admin.clip_links.bulk_remove') }}" class="p-4 rounded-2xl bg-card border border-border flex gap-3 items-center" onsubmit="return confirm('Remove all active clips for this channel name?')">
            @csrf
            <input type="text" name="channel_name" placeholder="Channel name to bulk remove..." class="px-3 py-1.5 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent flex-1" required>
            <button type="submit" class="px-4 py-1.5 bg-danger/20 hover:bg-danger/30 text-danger font-medium text-sm rounded-xl transition-colors">Bulk Remove Channel</button>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-card border border-border rounded-2xl overflow-hidden">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b border-border bg-surface/50 text-muted text-xs uppercase tracking-wider">
                    <th class="p-4 font-semibold">Title / URL</th>
                    <th class="p-4 font-semibold">Channel</th>
                    <th class="p-4 font-semibold">Trust</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($clipLinks as $clip)
                    <tr class="hover:bg-surface/30 transition-colors">
                        <td class="p-4">
                            <div class="font-medium text-text">{{ $clip->title?->title ?? 'Unknown Title' }}</div>
                            <a href="{{ $clip->url }}" target="_blank" class="text-xs text-accent truncate block max-w-md hover:underline">{{ $clip->url }}</a>
                        </td>
                        <td class="p-4 text-muted">{{ $clip->channel_name ?? '—' }}</td>
                        <td class="p-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold 
                                @if($clip->trust === 'official') bg-success/20 text-success 
                                @elseif($clip->trust === 'trusted') bg-accent/20 text-accent 
                                @else bg-elevated text-muted @endif">
                                {{ ucfirst($clip->trust) }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold 
                                @if($clip->status === 'active') bg-success/20 text-success 
                                @elseif($clip->status === 'reported') bg-warning/20 text-warning 
                                @else bg-danger/20 text-danger @endif">
                                {{ ucfirst($clip->status) }}
                            </span>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            @if($clip->video_platform === 'youtube')
                                <form action="{{ route('admin.clip_links.verify', $clip) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-accent hover:underline">Verify Now</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.clip_links.edit', $clip) }}" class="text-xs text-text hover:underline">Edit</a>
                            <form action="{{ route('admin.clip_links.destroy', $clip) }}" method="POST" class="inline" onsubmit="return confirm('Delete this clip link?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-danger hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-muted">No clip links found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $clipLinks->links() }}
</div>
@endsection

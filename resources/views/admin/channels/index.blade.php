@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Channel Allow-list</h1>
            <p class="text-sm text-muted">Manage official, trusted, and blocked YouTube channels</p>
        </div>
        <a href="{{ route('admin.channels.create') }}" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Add Channel</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.channels.index') }}" class="p-4 rounded-2xl bg-card border border-border flex gap-3 items-center">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search channel name or ID..." class="px-3 py-1.5 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent flex-1">
        <select name="trust" class="px-3 py-1.5 rounded-xl bg-surface border border-border text-sm text-text focus:outline-none focus:border-accent">
            <option value="">All Trust Levels</option>
            <option value="official" @selected(($filters['trust'] ?? '') === 'official')>Official</option>
            <option value="trusted" @selected(($filters['trust'] ?? '') === 'trusted')>Trusted</option>
            <option value="blocked" @selected(($filters['trust'] ?? '') === 'blocked')>Blocked</option>
        </select>
        <button type="submit" class="px-4 py-1.5 bg-elevated hover:bg-border text-text font-medium text-sm rounded-xl transition-colors">Filter</button>
    </form>

    {{-- Table --}}
    <div class="bg-card border border-border rounded-2xl overflow-hidden">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b border-border bg-surface/50 text-muted text-xs uppercase tracking-wider">
                    <th class="p-4 font-semibold">Channel Name</th>
                    <th class="p-4 font-semibold">Platform ID</th>
                    <th class="p-4 font-semibold">Trust Level</th>
                    <th class="p-4 font-semibold">Notes</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($channels as $channel)
                    <tr class="hover:bg-surface/30 transition-colors">
                        <td class="p-4 font-medium text-text">{{ $channel->channel_name }}</td>
                        <td class="p-4 font-mono text-xs text-muted">{{ $channel->channel_id ?? '—' }}</td>
                        <td class="p-4">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold 
                                @if($channel->trust === 'official') bg-success/20 text-success 
                                @elseif($channel->trust === 'trusted') bg-accent/20 text-accent 
                                @else bg-danger/20 text-danger @endif">
                                {{ ucfirst($channel->trust) }}
                            </span>
                        </td>
                        <td class="p-4 text-muted text-xs">{{ $channel->notes ?? '—' }}</td>
                        <td class="p-4 text-right space-x-2">
                            <a href="{{ route('admin.channels.edit', $channel) }}" class="text-xs text-text hover:underline">Edit</a>
                            <form action="{{ route('admin.channels.destroy', $channel) }}" method="POST" class="inline" onsubmit="return confirm('Remove channel from allow-list?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-danger hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-muted">No channels found in allow-list.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $channels->links() }}
</div>
@endsection

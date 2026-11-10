@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Reports Queue</h1>
            <p class="text-sm text-muted">Review reported clip links and moderate accordingly</p>
        </div>
        <div class="flex gap-2 text-sm font-medium">
            <a href="{{ route('admin.reports.index', ['status' => 'open']) }}" class="px-3 py-1.5 rounded-xl transition-colors {{ $currentStatus === 'open' ? 'bg-card text-accent' : 'text-muted hover:text-text' }}">Open</a>
            <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}" class="px-3 py-1.5 rounded-xl transition-colors {{ $currentStatus === 'resolved' ? 'bg-card text-accent' : 'text-muted hover:text-text' }}">Resolved</a>
            <a href="{{ route('admin.reports.index', ['status' => 'all']) }}" class="px-3 py-1.5 rounded-xl transition-colors {{ $currentStatus === 'all' ? 'bg-card text-accent' : 'text-muted hover:text-text' }}">All</a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($reports as $report)
            <div class="p-5 rounded-2xl bg-card border border-border space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-danger/20 text-danger uppercase">{{ $report->reason }}</span>
                            <span class="text-xs text-muted">Reported by {{ $report->user?->name ?? 'User' }} on {{ $report->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <p class="text-sm text-text mt-1.5 font-medium">Details: {{ $report->details ?? 'No details provided.' }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $report->status === 'open' ? 'bg-warning/20 text-warning' : 'bg-success/20 text-success' }}">
                        {{ ucfirst($report->status) }}
                    </span>
                </div>

                @if ($report->clipLink)
                    <div class="p-3 rounded-xl bg-surface border border-border flex items-center justify-between text-sm">
                        <div>
                            <span class="text-xs text-muted block">Clip Link for: <strong class="text-text">{{ $report->clipLink->title?->title ?? 'Title' }}</strong></span>
                            <a href="{{ $report->clipLink->url }}" target="_blank" class="text-accent hover:underline text-xs truncate block max-w-xl">{{ $report->clipLink->url }}</a>
                            <span class="text-xs text-muted mt-0.5 block">Channel: {{ $report->clipLink->channel_name ?? 'Unknown' }} | Status: {{ $report->clipLink->status }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-danger">Associated clip link was deleted.</p>
                @endif

                @if ($report->status === 'open')
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-border">
                        @if ($report->clipLink)
                            <form action="{{ route('admin.reports.restore', $report) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-success/20 hover:bg-success/30 text-success text-xs font-semibold rounded-lg transition-colors">Restore Link</button>
                            </form>
                            <form action="{{ route('admin.reports.remove_link', $report) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-danger/20 hover:bg-danger/30 text-danger text-xs font-semibold rounded-lg transition-colors">Remove Link</button>
                            </form>
                            @if (filled($report->clipLink->channel_name))
                                <form action="{{ route('admin.reports.block_channel', $report) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-danger text-white text-xs font-semibold rounded-lg transition-colors">Block Channel</button>
                                </form>
                            @endif
                        @endif

                        <form action="{{ route('admin.reports.resolve', $report) }}" method="POST" class="flex items-center gap-2 ml-auto">
                            @csrf
                            <input type="text" name="note" placeholder="Resolution note..." class="px-3 py-1 rounded-lg bg-surface border border-border text-xs text-text focus:outline-none focus:border-accent w-48">
                            <button type="submit" class="px-3 py-1 bg-elevated hover:bg-border text-text text-xs font-semibold rounded-lg transition-colors">Resolve with Note</button>
                        </form>
                    </div>
                @else
                    <div class="text-xs text-muted pt-2 border-t border-border flex justify-between">
                        <span>Resolved by admin ({{ $report->resolver?->name ?? 'Admin' }}) at {{ $report->resolved_at?->format('Y-m-d H:i') }}</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-12 text-center text-muted bg-card border border-border rounded-2xl">No reports in the queue.</div>
        @endforelse
    </div>

    {{ $reports->links() }}
</div>
@endsection

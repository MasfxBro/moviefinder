{{-- resources/views/admin/feedback/index.blade.php --}}
@extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Feedback</h1>
    <div class="flex gap-2 text-sm">
        @foreach (['', 'new', 'read', 'in_progress', 'done', 'archived'] as $s)
            <a href="{{ route('admin.feedback.index', array_filter(['status' => $s, 'type' => $type])) }}"
               class="px-3 py-1 rounded border {{ $status === $s ? 'border-accent text-accent' : 'border-border text-muted' }}">
                {{ $s === '' ? 'All' : ucfirst(str_replace('_', ' ', $s)) }}
            </a>
        @endforeach
    </div>
</div>

@if ($feedbacks->isEmpty())
    <p class="text-muted">No feedback items match the current filter.</p>
@else
    <table class="w-full text-sm border border-border rounded-lg overflow-hidden">
        <thead class="bg-elevated">
            <tr>
                <th class="p-2 text-left border-b border-border">User</th>
                <th class="p-2 text-left border-b border-border">Type</th>
                <th class="p-2 text-left border-b border-border">Message</th>
                <th class="p-2 text-left border-b border-border">Status</th>
                <th class="p-2 text-left border-b border-border">Date</th>
                <th class="p-2 border-b border-border"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($feedbacks as $feedback)
                <tr class="border-b border-border hover:bg-elevated">
                    <td class="p-2">{{ $feedback->user?->email ?? 'Unknown' }}</td>
                    <td class="p-2">{{ $feedback->type }}</td>
                    <td class="p-2 max-w-xs truncate">{{ $feedback->message }}</td>
                    <td class="p-2">{{ $feedback->status }}</td>
                    <td class="p-2">{{ $feedback->created_at?->format('d M Y') }}</td>
                    <td class="p-2">
                        <form method="POST" action="{{ route('admin.feedback.update_status', $feedback) }}" class="flex gap-1 items-center">
                            @csrf
                            <select name="status" class="text-xs rounded border-border bg-card text-text py-1">
                                @foreach (['new', 'read', 'in_progress', 'done', 'archived'] as $s)
                                    <option value="{{ $s }}" @selected($feedback->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="text-xs bg-accent text-white px-2 py-1 rounded">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $feedbacks->links() }}</div>
@endif
@endsection

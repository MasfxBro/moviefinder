{{-- resources/views/admin/announcements/edit.blade.php --}}
@extends('admin.layout')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Edit Announcement</h1>
</div>

<form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-text mb-1">Kind</label>
        <select name="kind" class="w-full rounded border-border bg-card text-text" required>
            @foreach (['info', 'warning', 'maintenance'] as $kind)
                <option value="{{ $kind }}" @selected($announcement->kind === $kind)>{{ ucfirst($kind) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-text mb-1">Message</label>
        <textarea name="message" maxlength="300" rows="3" class="w-full rounded border-border bg-card text-text" required>{{ $announcement->message }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-text mb-1">Starts at</label>
        <input type="datetime-local" name="starts_at" value="{{ $announcement->starts_at?->format('Y-m-d\TH:i') }}" class="w-full rounded border-border bg-card text-text">
    </div>
    <div>
        <label class="block text-sm font-medium text-text mb-1">Ends at</label>
        <input type="datetime-local" name="ends_at" value="{{ $announcement->ends_at?->format('Y-m-d\TH:i') }}" class="w-full rounded border-border bg-card text-text">
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" @checked($announcement->is_active)>
        <label for="is_active" class="text-sm text-text">Active</label>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg">Save</button>
        <a href="{{ route('admin.announcements.index') }}" class="text-muted underline px-4 py-2">Cancel</a>
    </div>
</form>

<form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="mt-6">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-sm text-rose-400 underline" onclick="return confirm('Delete this announcement?')">Delete announcement</button>
</form>
@endsection

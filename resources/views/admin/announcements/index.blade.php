{{-- resources/views/admin/announcements/index.blade.php --}}
@extends('admin.layout')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Announcements</h1>
    <a href="{{ route('admin.announcements.create') }}" class="bg-accent text-white px-4 py-2 rounded-lg">Create Announcement</a>
</div>
<table class="w-full border">
    <thead>
        <tr>
            <th class="p-2 border">Kind</th>
            <th class="p-2 border">Message</th>
            <th class="p-2 border">Active</th>
            <th class="p-2 border">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($announcements as $ann)
        <tr>
            <td class="p-2 border">{{ $ann->kind }}</td>
            <td class="p-2 border">{{ $ann->message }}</td>
            <td class="p-2 border">{{ $ann->is_active ? 'Yes' : 'No' }}</td>
            <td class="p-2 border">
                <a href="{{ route('admin.announcements.edit', $ann) }}" class="text-blue-500">Edit</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

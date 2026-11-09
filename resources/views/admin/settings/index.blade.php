{{-- resources/views/admin/settings/index.blade.php --}}
@extends('admin.layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Settings</h1>
    <table class="w-full table-auto border">
        <thead class="bg-gray-100">
        <tr>
            <th class="px-4 py-2">Key</th>
            <th class="px-4 py-2">Value</th>
            <th class="px-4 py-2">Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach($settings as $setting)
            <tr class="border-t">
                <td class="px-4 py-2">{{ $setting->key }}</td>
                <td class="px-4 py-2">
                    <form method="POST" action="{{ route('admin.settings.update', ['key' => $setting->key]) }}" class="flex items-center">
                        @csrf
                        <input type="text" name="value" value="{{ $setting->value }}" class="border rounded px-2 py-1 flex-1" />
                        <button type="submit" class="ml-2 bg-blue-500 text-white px-3 py-1 rounded">Save</button>
                    </form>
                </td>
                <td class="px-4 py-2"></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

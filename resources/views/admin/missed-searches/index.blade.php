<?php
/**
 * Blade view for admin missed searches.
 *
 * Variables:
 * - \$misses (LengthAwarePaginator) of SearchMiss
 * - \$currentStatus (string)
 * - \$currentSort (string)
 */
?>
@extends('admin.layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Missed Searches</h1>
    <div class="mb-4">
        <a href="{{ route('admin.missed_searches.index', ['status' => 'open']) }}" class="mr-2 {{ $currentStatus === 'open' ? 'font-bold' : '' }}">Open</a>
        <a href="{{ route('admin.missed_searches.index', ['status' => 'all']) }}" class="mr-2 {{ $currentStatus === 'all' ? 'font-bold' : '' }}">All</a>
        <a href="{{ route('admin.missed_searches.index', ['status' => 'ignored']) }}" class="{{ $currentStatus === 'ignored' ? 'font-bold' : '' }}">Ignored</a>
    </div>
    <table class="w-full table-auto">
        <thead>
            <tr>
                <th>Query</th>
                <th>Hits</th>
                <th>Last Seen</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($misses as $m)
            <tr class="border-t">
                <td>{{ $m->query_norm }}</td>
                <td>{{ $m->hits }}</td>
                <td>{{ $m->last_seen_at->format('Y-m-d') }}</td>
                <td>{{ $m->status }}</td>
                <td>
                    @if($m->status === 'open')
                        <form method="POST" action="{{ route('admin.missed_searches.convert', $m) }}" class="inline">@csrf
                            <input type="hidden" name="action_type" value="request">
                            <button type="submit" class="text-blue-600">Create Request</button>
                        </form>
                        <form method="POST" action="{{ route('admin.missed_searches.convert', $m) }}" class="inline ml-2">@csrf
                            <input type="hidden" name="action_type" value="import">
                            <input type="text" name="tmdb_id" placeholder="TMDB ID" class="border px-1" size="5">
                            <input type="hidden" name="media_type" value="movie">
                            <button type="submit" class="text-green-600">Import</button>
                        </form>
                        <form method="POST" action="{{ route('admin.missed_searches.ignore', $m) }}" class="inline ml-2">@csrf
                            <button type="submit" class="text-gray-600">Ignore</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $misses->links() }}
@endsection

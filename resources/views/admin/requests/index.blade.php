<?php
/**
 * Blade view for admin request queue.
 *
 * Variables:
 * - \$requests (LengthAwarePaginator) of TitleRequest
 * - \$currentStatus (string)
 * - \$pendingCount (int)
 */
?>
@extends('admin.layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Title Requests</h1>
    <div class="mb-4">
        <a href="{{ route('admin.requests.index', ['status' => 'pending']) }}" class="mr-2 {{ $currentStatus === 'pending' ? 'font-bold' : '' }}">Pending ({{ $pendingCount }})</a>
        <a href="{{ route('admin.requests.index', ['status' => 'all']) }}" class="{{ $currentStatus === 'all' ? 'font-bold' : '' }}">All</a>
    </div>
    @if($requests->isEmpty())
        <p>No requests found.</p>
    @else
        <table class="w-full table-auto">
            <thead><tr><th>Title</th><th>User</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($requests as $req)
                <tr class="border-t">
                    <td>{{ $req->title_text }}</td>
                    <td>{{ $req->user->email }}</td>
                    <td>{{ $req->status->value }}</td>
                    <td>
                        @if($req->status->isPending())
                            <form method="POST" action="{{ route('admin.requests.approve', $req) }}" class="inline">@csrf
                                <input type="hidden" name="tmdb_id" value="0">
                                <button type="submit" class="text-green-600">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.requests.reject', $req) }}" class="inline ml-2">@csrf
                                <button type="submit" class="text-red-600">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $requests->links() }}
    @endif
@endsection

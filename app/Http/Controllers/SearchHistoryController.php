<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SearchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SearchHistoryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', SearchHistory::class);

        $history = $request->user()
            ->searchHistory()
            ->latest('created_at')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($history);
        }

        return view('history.index', [
            'history' => $history,
        ]);
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('clear', SearchHistory::class);

        $request->user()->searchHistory()->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Search history cleared.']);
        }

        return redirect()->route('history.index')->with('status', 'Search history cleared.');
    }

    public function recent(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', SearchHistory::class);

        $recent = $request->user()
            ->searchHistory()
            ->latest('created_at')
            ->limit(20)
            ->pluck('query')
            ->unique()
            ->values()
            ->take(5);

        return response()->json($recent);
    }
}

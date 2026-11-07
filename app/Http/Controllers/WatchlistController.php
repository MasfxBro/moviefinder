<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PlanResolver;
use App\Models\Title;
use App\Models\Watchlist;
use App\Services\Ai\PlanCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WatchlistController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', Watchlist::class);

        $items = $request->user()->watchlist()->whereHas('title', fn ($query) => $query->where('is_hidden', false)->where('adult', false))->with(['title.genres'])->latest('created_at')->paginate(24);

        if ($request->wantsJson()) {
            return response()->json($items);
        }

        return view('watchlist.index', compact('items'));
    }

    public function toggle(Request $request, Title $title, PlanResolver $planResolver, PlanCatalog $planCatalog): JsonResponse
    {
        Gate::authorize('toggle', Watchlist::class);

        $user = $request->user();
        $existing = $user->watchlist()->where('title_id', $title->id)->first();

        if ($existing) {
            Gate::authorize('delete', $existing);
            $existing->delete();

            return response()->json(['action' => 'removed']);
        }

        Gate::authorize('create', Watchlist::class);
        $max = (int) $planCatalog->get($planResolver->resolve($user))['watchlist_max'];
        if ($user->watchlist()->count() >= $max) {
            return response()->json(['message' => "Your watchlist is full ({$max} titles)."], 422);
        }

        try {
            $user->watchlist()->create(['title_id' => $title->id]);
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'unique') && ! str_contains(strtolower($exception->getMessage()), 'duplicate')) {
                throw $exception;
            }
        }

        return response()->json(['action' => 'added']);
    }
}

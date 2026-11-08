<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Http\Resources\TitleSearchResource;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TitleSearchController extends Controller
{
    public function __invoke(Request $request, MetadataProvider $tmdb)
    {
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return $request->expectsJson()
                ? response()->json([])
                : view('search', ['results' => collect(), 'savedTitleIds' => []]);
        }

        if (($user = $request->user()) && ! $request->boolean('live')) {
            $user->searchHistory()->create([
                'type' => 'normal',
                'query' => mb_substr($term, 0, 200),
            ]);
        }

        $escaped = addcslashes($term, '%_');
        $local = Title::query()
            ->where('adult', false)
            ->where('is_hidden', false)
            ->where(function ($query) use ($escaped) {
                $query->whereRaw('MATCH(title, original_title) AGAINST(? IN BOOLEAN MODE)', [$escaped.'*'])
                    ->orWhere('title', 'LIKE', "%{$escaped}%")
                    ->orWhere('original_title', 'LIKE', "%{$escaped}%");
            })
            ->orderByDesc('popularity')
            ->limit(10)
            ->get();
        if ($local->isNotEmpty()) {
            if ($request->expectsJson()) {
                return TitleSearchResource::collection($local);
            }

            $savedTitleIds = $request->user()->hasVerifiedEmail()
                ? $request->user()->watchlist()->pluck('title_id')->all()
                : [];

            return view('search', ['results' => $local, 'savedTitleIds' => $savedTitleIds]);
        }
        // record miss counter
        DB::table('search_misses')
            ->updateOrInsert(
                ['query_norm' => strtolower($term)],
                [
                    'hits' => DB::raw('hits + 1'),
                    'last_seen_at' => now(),
                    'status' => 'open',
                ]
            );
        if ($request->expectsJson()) {
            // TMDB fallback (cached in client)
            $tmdbResults = collect(MediaType::cases())
                ->flatMap(fn (MediaType $type) => $tmdb->search($term, $type))
                ->sortByDesc(fn ($item) => $item->popularity ?? 0)
                ->take(10)
                ->map(fn ($item) => [
                    'id' => $item->tmdbId,
                    'slug' => 'tmdb-'.$item->tmdbId,
                    'title' => $item->title,
                    'original_title' => $item->originalTitle,
                    'media_type' => $item->type->value,
                    'release_year' => $item->releaseYear,
                    'poster_path' => $item->posterPath,
                    'not_in_catalog' => true,
                ])
                ->values();

            return response()->json($tmdbResults);
        }

        return view('search', ['results' => collect(), 'savedTitleIds' => []]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Http\Resources\TitleSearchResource;
use App\Models\Title;
use App\Services\Tmdb\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TitleSearchController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb)
    {
        $term = $request->query('q', '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }
        $escaped = addcslashes($term, '%_');
        $local = Title::query()
            ->where('adult', false)
            ->whereRaw('MATCH(title, original_title) AGAINST(? IN BOOLEAN MODE)', [$escaped.'*'])
            ->orWhere('title', 'LIKE', "%{$escaped}%")
            ->orWhere('original_title', 'LIKE', "%{$escaped}%")
            ->limit(10)
            ->get();
        if ($local->isNotEmpty()) {
            return TitleSearchResource::collection($local);
        }
        // TMDB fallback (cached in client)
        $tmdbResults = $tmdb->search($term, MediaType::Movie);
        $collection = collect($tmdbResults)->map(function ($item) {
            $item['not_in_catalog'] = true;

            return $item;
        });
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

        return response()->json($collection);
    }
}

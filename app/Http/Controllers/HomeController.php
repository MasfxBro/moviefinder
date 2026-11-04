<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\Title;
use App\Services\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly MetadataProvider $metadataProvider,
    ) {}

    public function __invoke(Request $request): View
    {
        if (! $request->user()) {
            return $this->landingView();
        }

        return $this->homeView();
    }

    private function landingView(): View
    {
        $plans = [
            'free' => $this->getPlanData('free'),
            'plus' => $this->getPlanData('plus'),
            'pro' => $this->getPlanData('pro'),
        ];

        $postersData = Cache::remember('v1:landing:posters', 86400, function () {
            try {
                return DB::table('titles')
                    ->whereNotNull('poster_path')
                    ->where('is_hidden', false)
                    ->orderByDesc('popularity')
                    ->limit(8)
                    ->get(['title', 'poster_path'])
                    ->map(fn ($item) => [
                        'title' => $item->title,
                        'poster_path' => $item->poster_path,
                    ])
                    ->all();
            } catch (\Exception $e) {
                return [];
            }
        });

        $posters = collect($postersData)->map(fn ($item) => (object) $item);

        return view('welcome', [
            'plans' => $plans,
            'posters' => $posters,
        ]);
    }

    private function homeView(): View
    {
        // 1. Trending (cached daily from TMDB, plain arrays only)
        $trendingData = Cache::remember('v1:home:trending', 86400, function () {
            try {
                $movies = $this->metadataProvider->trending(MediaType::Movie);
                $tv = $this->metadataProvider->trending(MediaType::Tv);

                $merged = [];
                $max = max(count($movies), count($tv));
                for ($i = 0; $i < $max; $i++) {
                    if (isset($movies[$i])) {
                        $merged[] = $movies[$i];
                    }
                    if (isset($tv[$i])) {
                        $merged[] = $tv[$i];
                    }
                }

                return array_map(fn ($item) => [
                    'tmdb_id' => $item->tmdbId,
                    'title' => $item->title,
                    'year' => $item->releaseYear,
                    'poster' => $item->posterPath,
                    'genre_ids' => $item->genreIds,
                    'media_type' => $item->type->value,
                ], array_slice($merged, 0, 15));
            } catch (\Throwable $e) {
                return [];
            }
        });

        // Resolve genre map and local titles for trending items
        $genreMap = Cache::remember('v1:genres:map', 86400, function () {
            try {
                return Genre::pluck('name', 'id')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });

        $trendingTmdbIds = array_map(fn ($item) => $item['tmdb_id'], $trendingData);
        $localTrendingTitles = ! empty($trendingTmdbIds)
            ? Title::whereIn('tmdb_id', $trendingTmdbIds)->where('is_hidden', false)->with('genres')->get()->keyBy('tmdb_id')
            : collect();

        $trending = array_map(function (array $dto) use ($genreMap, $localTrendingTitles) {
            $local = $localTrendingTitles->get($dto['tmdb_id']);
            if ($local) {
                return [
                    'id' => $local->id,
                    'title' => $local->title,
                    'year' => $local->release_year,
                    'poster' => $local->poster_path,
                    'url' => url("/titles/{$local->slug}"),
                    'genres' => $local->genres->pluck('name')->all(),
                    'media_type' => $local->media_type,
                ];
            }

            $genreNames = array_values(array_filter(array_map(fn ($id) => $genreMap[$id] ?? null, $dto['genre_ids'])));

            return [
                'id' => null,
                'title' => $dto['title'],
                'year' => $dto['year'],
                'poster' => $dto['poster'],
                'url' => url("/titles/tmdb-{$dto['tmdb_id']}"),
                'genres' => $genreNames,
                'media_type' => $dto['media_type'],
            ];
        }, $trendingData);

        // 2. Popular (local catalog)
        $popular = Title::where('is_hidden', false)
            ->orderByDesc('popularity')
            ->with('genres')
            ->limit(15)
            ->get();

        // 3. Top Rated (local catalog)
        $topRated = Title::where('is_hidden', false)
            ->orderByDesc('vote_average')
            ->orderByDesc('vote_count')
            ->with('genres')
            ->limit(15)
            ->get();

        // 4. Movies (local catalog)
        $movies = Title::where('is_hidden', false)
            ->where('media_type', MediaType::Movie->value)
            ->orderByDesc('popularity')
            ->with('genres')
            ->limit(15)
            ->get();

        // 5. TV Shows (local catalog)
        $tvShows = Title::where('is_hidden', false)
            ->where('media_type', MediaType::Tv->value)
            ->orderByDesc('popularity')
            ->with('genres')
            ->limit(15)
            ->get();

        $isEmpty = ! Title::where('is_hidden', false)->exists();

        return view('home', [
            'isEmpty' => $isEmpty,
            'trending' => $trending,
            'popular' => $popular,
            'topRated' => $topRated,
            'movies' => $movies,
            'tvShows' => $tvShows,
        ]);
    }

    private function getPlanData(string $key): array
    {
        return [
            'price' => $this->settings->get("plan.$key.price_idr"),
            'daily_units' => $this->settings->get("plan.$key.daily_units"),
            'smart_searches' => $this->settings->get("plan.$key.smart_searches_daily"),
            'results_per_answer' => $this->settings->get("plan.$key.title_cards"),
            'payment_methods' => config("billing.methods.$key.manual", []),
        ];
    }
}

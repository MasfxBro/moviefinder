<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BrowseTitlesRequest;
use App\Models\Genre;
use App\Services\Catalog\TitleFilter;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class BrowseController extends Controller
{
    public function __invoke(BrowseTitlesRequest $request, TitleFilter $filter): View
    {
        $filters = $request->validated();
        $genres = Genre::orderBy('name')->get();

        $titles = $filter->paginate($filters);
        $savedTitleIds = $request->user()->hasVerifiedEmail()
            ? $request->user()->watchlist()->pluck('title_id')->all()
            : [];

        return view('browse', [
            'titles' => $titles,
            'savedTitleIds' => $savedTitleIds,
            'filters' => $filters,
            'genres' => $genres,
            'chips' => $this->chips($filters, $genres->pluck('name', 'id')->all()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $genreNames
     * @return array<int, array{label: string, url: string}>
     */
    private function chips(array $filters, array $genreNames): array
    {
        $query = Arr::only($filters, ['type', 'genres', 'year_from', 'year_to', 'director', 'min_rating', 'sort']);
        $chips = [];

        $link = fn (array $params): string => route('browse', $params);

        if (isset($query['type'])) {
            $chips[] = [
                'label' => $query['type'] === 'movie' ? 'Movies' : 'TV Shows',
                'url' => $link(Arr::except($query, 'type')),
            ];
        }

        foreach ($query['genres'] ?? [] as $genreId) {
            $remaining = array_values(array_diff($query['genres'], [$genreId]));
            $chips[] = [
                'label' => $genreNames[$genreId] ?? (string) $genreId,
                'url' => $link($remaining === []
                    ? Arr::except($query, 'genres')
                    : array_merge(Arr::except($query, 'genres'), ['genres' => $remaining])),
            ];
        }

        if (isset($query['year_from'])) {
            $chips[] = ['label' => 'From '.$query['year_from'], 'url' => $link(Arr::except($query, 'year_from'))];
        }

        if (isset($query['year_to'])) {
            $chips[] = ['label' => 'To '.$query['year_to'], 'url' => $link(Arr::except($query, 'year_to'))];
        }

        if (isset($query['director'])) {
            $chips[] = ['label' => 'Director: '.$query['director'], 'url' => $link(Arr::except($query, 'director'))];
        }

        if (isset($query['min_rating'])) {
            $chips[] = ['label' => 'Rating '.$query['min_rating'].'+', 'url' => $link(Arr::except($query, 'min_rating'))];
        }

        if (isset($query['sort'])) {
            $chips[] = [
                'label' => 'Sorted by '.ucfirst($query['sort']),
                'url' => $link(Arr::except($query, 'sort')),
            ];
        }

        return $chips;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Title;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TitleFilter
{
    public const PER_PAGE = 24;

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    public const SORTS = [
        'popularity' => ['popularity', 'desc'],
        'rating' => ['vote_average', 'desc'],
        'newest' => ['release_year', 'desc'],
    ];

    /**
     * @param  array{type?: string|null, genres?: array<int, int>|null, year_from?: int|null, year_to?: int|null, director?: string|null, min_rating?: int|float|string|null, sort?: string|null}  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        [$sortColumn, $sortDirection] = self::SORTS[$filters['sort'] ?? 'popularity'] ?? self::SORTS['popularity'];

        return Title::query()
            ->where('is_hidden', false)
            ->with('genres')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('media_type', $type))
            ->when($filters['genres'] ?? [], function ($query, array $genreIds) {
                foreach ($genreIds as $genreId) {
                    $query->whereHas('genres', fn ($genres) => $genres->where('genres.id', $genreId));
                }
            })
            ->when($filters['year_from'] ?? null, fn ($query, $year) => $query->where('release_year', '>=', $year))
            ->when($filters['year_to'] ?? null, fn ($query, $year) => $query->where('release_year', '<=', $year))
            ->when($filters['director'] ?? null, fn ($query, $name) => $query->whereHas(
                'people',
                fn ($people) => $people
                    ->whereIn('role', ['director', 'creator'])
                    ->where('name', 'like', '%'.$this->escapeLike($name).'%')
            ))
            ->when($filters['min_rating'] ?? null, fn ($query, $rating) => $query->where('vote_average', '>=', $rating))
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}

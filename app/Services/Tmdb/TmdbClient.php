<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use App\Contracts\MetadataProvider;
use App\DTOs\TitleData;
use App\Enums\MediaType;
use App\Exceptions\Tmdb\TmdbConnectionException;
use App\Exceptions\Tmdb\TmdbNotFoundException;
use App\Exceptions\Tmdb\TmdbRateLimitException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class TmdbClient implements MetadataProvider
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private const TIMEOUT = 8;

    private const MAX_RETRIES = 2;

    private const CACHE_TTL = 86400; // 24h

    private const RATE_LIMIT = 10; // req/s

    public function __construct(
        private readonly string $token,
        private readonly TmdbMapper $mapper,
    ) {}

    public function search(string $query, MediaType $type): array
    {
        $cacheKey = "tmdb:search:{$type->value}:".md5($query);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $type) {
            $data = $this->request("search/{$type->value}", [
                'query' => $query,
                'include_adult' => 'false',
            ]);

            $filtered = array_filter($data['results'] ?? [], fn (array $item) => ! ($item['adult'] ?? false));

            return array_values(array_map(
                fn (array $item) => $this->mapper->mapToTitleData($item, $type),
                $filtered
            ));
        });
    }

    public function details(int $id, MediaType $type): TitleData
    {
        $cacheKey = "tmdb:details:{$type->value}:{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id, $type) {
            $data = $this->request("{$type->value}/{$id}", [
                'append_to_response' => 'credits,videos',
            ]);

            return $this->mapper->mapToTitleData($data, $type);
        });
    }

    public function trending(MediaType $type): array
    {
        $cacheKey = "tmdb:trending:{$type->value}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $data = $this->request("trending/{$type->value}/week");

            $filtered = array_filter($data['results'] ?? [], fn (array $item) => ! ($item['adult'] ?? false));

            return array_values(array_map(
                fn (array $item) => $this->mapper->mapToTitleData($item, $type),
                $filtered
            ));
        });
    }

    public function popular(MediaType $type): array
    {
        $cacheKey = "tmdb:popular:{$type->value}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $data = $this->request("{$type->value}/popular", [
                'include_adult' => 'false',
            ]);

            $filtered = array_filter($data['results'] ?? [], fn (array $item) => ! ($item['adult'] ?? false));

            return array_values(array_map(
                fn (array $item) => $this->mapper->mapToTitleData($item, $type),
                $filtered
            ));
        });
    }

    public function topRated(MediaType $type): array
    {
        $cacheKey = "tmdb:top_rated:{$type->value}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $data = $this->request("{$type->value}/top_rated", [
                'include_adult' => 'false',
            ]);

            $filtered = array_filter($data['results'] ?? [], fn (array $item) => ! ($item['adult'] ?? false));

            return array_values(array_map(
                fn (array $item) => $this->mapper->mapToTitleData($item, $type),
                $filtered
            ));
        });
    }

    public function genres(MediaType $type): array
    {
        $cacheKey = "tmdb:genres:{$type->value}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $data = $this->request("genre/{$type->value}/list");

            $genres = [];
            foreach ($data['genres'] ?? [] as $genre) {
                $genres[(string) $genre['id']] = $genre['name'];
            }

            return $genres;
        });
    }

    public function watchProviders(int $id, MediaType $type, string $region): array
    {
        $cacheKey = "tmdb:watch:{$type->value}:{$id}:{$region}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id, $type, $region) {
            $data = $this->request("{$type->value}/{$id}/watch/providers");

            $providers = $data['results'][$region] ?? [];
            $flatrate = $providers['flatrate'] ?? [];

            $result = [];
            foreach ($flatrate as $provider) {
                $result[(string) $provider['provider_id']] = [
                    'logo_path' => $provider['logo_path'],
                    'provider_name' => $provider['provider_name'],
                ];
            }

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $params = []): array
    {
        $this->rateLimit();

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $this->http()->get(self::BASE_URL.'/'.$endpoint, $params);

                if ($response->status() === 404) {
                    throw new TmdbNotFoundException('Resource not found');
                }

                if ($response->status() === 429) {
                    throw new TmdbRateLimitException('Rate limit exceeded');
                }

                if ($response->failed()) {
                    throw new TmdbConnectionException("TMDB API error: {$response->status()}");
                }

                return $response->json();
            } catch (ConnectionException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < self::MAX_RETRIES) {
                    usleep(1000000 * $attempt); // 1s, 2s
                }
            }
        }

        throw new TmdbConnectionException('Connection failed after retries', 0, $lastException);
    }

    private function http(): PendingRequest
    {
        return Http::timeout(self::TIMEOUT)
            ->withToken($this->token)
            ->acceptJson();
    }

    private function rateLimit(): void
    {
        RateLimiter::attempt(
            'tmdb-api',
            self::RATE_LIMIT,
            fn () => true,
            1
        );
    }
}

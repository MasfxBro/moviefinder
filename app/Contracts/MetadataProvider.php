<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\TitleData;
use App\Enums\MediaType;

interface MetadataProvider
{
    /**
     * @return array<TitleData>
     */
    public function search(string $query, MediaType $type): array;

    public function details(int $id, MediaType $type): TitleData;

    /**
     * @return array<TitleData>
     */
    public function trending(MediaType $type): array;

    /**
     * @return array<TitleData>
     */
    public function popular(MediaType $type): array;

    /**
     * @return array<TitleData>
     */
    public function topRated(MediaType $type): array;

    /**
     * @return array<string, string> genre_id => name
     */
    public function genres(MediaType $type): array;

    /**
     * @return array<string, array{logo_path: string, provider_name: string}>
     */
    public function watchProviders(int $id, MediaType $type, string $region): array;
}

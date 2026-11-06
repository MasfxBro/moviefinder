<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TitleSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Title $title */
        $title = $this->resource;

        return [
            'id' => $title->id,
            'slug' => $title->slug,
            'title' => $title->title,
            'original_title' => $title->original_title,
            'media_type' => $title->media_type,
            'release_year' => $title->release_year,
            'poster_path' => $title->poster_path,
            'not_in_catalog' => false,
        ];
    }
}

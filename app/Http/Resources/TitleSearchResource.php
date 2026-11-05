<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TitleSearchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'original_title' => $this->original_title,
            'media_type' => $this->media_type,
            'poster_path' => $this->poster_path,
            'not_in_catalog' => $this->when(isset($this->not_in_catalog), $this->not_in_catalog, false),
        ];
    }
}

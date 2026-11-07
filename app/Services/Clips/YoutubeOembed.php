<?php

declare(strict_types=1);

namespace App\Services\Clips;

use Illuminate\Support\Facades\Http;
use Throwable;

class YoutubeOembed
{
    public function check(PlatformUrl $url): ?array
    {
        if ($url->platform !== 'youtube' || $url->videoId === null) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)->timeout(5)->get('https://www.youtube.com/oembed', [
                'url' => $url->canonicalUrl,
                'format' => 'json',
            ]);
        } catch (Throwable) {
            return null;
        }

        return $response->ok() && is_array($response->json()) ? $response->json() : null;
    }
}

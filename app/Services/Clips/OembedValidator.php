<?php

declare(strict_types=1);

namespace App\Services\Clips;

use Illuminate\Support\Facades\Http;
use Throwable;

class OembedValidator
{
    public function check(PlatformUrl $url): bool
    {
        $endpoint = match ($url->platform) {
            'vimeo' => 'https://vimeo.com/api/oembed.json',
            'dailymotion' => 'https://www.dailymotion.com/services/oembed',
            'tiktok' => 'https://www.tiktok.com/oembed',
            default => null,
        };
        if ($endpoint === null) {
            return false;
        }

        try {
            return Http::connectTimeout(5)->timeout(5)->get($endpoint, ['url' => $url->canonicalUrl])->ok();
        } catch (Throwable) {
            return false;
        }
    }
}

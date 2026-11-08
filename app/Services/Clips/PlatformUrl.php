<?php

declare(strict_types=1);

namespace App\Services\Clips;

final readonly class PlatformUrl
{
    private const HOSTS = [
        'youtube' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
        'vimeo' => ['vimeo.com', 'player.vimeo.com'],
        'dailymotion' => ['dailymotion.com', 'dai.ly'],
        'tiktok' => ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'],
        'external' => ['instagram.com', 'www.instagram.com', 'facebook.com', 'www.facebook.com', 'fb.watch', 'x.com', 'twitter.com'],
    ];

    private function __construct(public string $platform, public string $canonicalUrl, public ?string $videoId) {}

    public static function parse(string $url): ?self
    {
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\\\\]/', $url) === 1) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        $platform = null;
        foreach (self::HOSTS as $candidate => $hosts) {
            if (in_array($host, $hosts, true)) {
                $platform = $candidate;
                break;
            }
        }
        if ($platform === null) {
            return null;
        }

        if ($platform === 'youtube') {
            $id = null;
            if ($host === 'youtu.be') {
                $id = trim($parts['path'] ?? '', '/');
            } elseif ($parts['path'] === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})$~', $parts['path'], $match) === 1) {
                $id = $match[1];
            }
            if (! is_string($id) || preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) !== 1) {
                return null;
            }

            return new self('youtube', 'https://www.youtube.com/watch?v='.$id, $id);
        }

        if ($platform === 'vimeo' && preg_match('~^/(?:video/)?([0-9]{1,12})/?$~', $parts['path'] ?? '', $match) === 1) {
            return new self($platform, 'https://vimeo.com/'.$match[1], $match[1]);
        }
        if ($platform === 'dailymotion' && preg_match('~^/(?:video/)?([A-Za-z0-9]{1,20})/?$~', $parts['path'] ?? '', $match) === 1) {
            return new self($platform, 'https://www.dailymotion.com/video/'.$match[1], $match[1]);
        }
        if ($platform === 'tiktok' && preg_match('~^/@[A-Za-z0-9._-]{1,24}/video/[0-9]{1,30}/?$~', $parts['path'] ?? '') === 1) {
            return new self($platform, 'https://www.tiktok.com'.$parts['path'], null);
        }
        if ($platform === 'external' && preg_match('~^/[A-Za-z0-9._/@-]{1,180}/?$~', $parts['path'] ?? '') === 1 && ! isset($parts['query'])) {
            return new self($platform, 'https://'.$host.$parts['path'], null);
        }

        return null;
    }
}

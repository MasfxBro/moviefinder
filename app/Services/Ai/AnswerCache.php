<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AnswerCache
{
    public function get(string $text): ?array
    {
        return $this->containsPersonalData($text) ? null : Cache::get($this->key($text));
    }

    public function put(string $text, array $answer): void
    {
        if (! $this->containsPersonalData($text)) {
            Cache::put($this->key($text), $answer, 86400);
        }
    }

    private function key(string $text): string
    {
        return 'ai:answer:'.hash('sha256', Str::squish(Str::lower($text)));
    }

    private function containsPersonalData(string $text): bool
    {
        return preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text) === 1
            || preg_match('/(?<!\w)(?:\+?\d[\d\s().-]{7,}\d)(?!\w)/', $text) === 1
            || preg_match('/\b(?:my name is|i am|i live at|alamat saya|nama saya|nomor saya)\b/i', $text) === 1;
    }
}

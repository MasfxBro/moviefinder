<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AiCheck extends Command
{
    protected $signature = 'ai:check';

    protected $description = 'Check Gemini configuration and capabilities without revealing keys';

    public function handle(): int
    {
        if (config('ai.driver') === 'fake') {
            $this->info('AI driver: fake. The real check runs in task T-43.');

            return self::SUCCESS;
        }

        $models = array_filter([config('ai.models.standard'), config('ai.models.smart')]);
        foreach (['free' => config('services.gemini.free_key'), 'paid' => config('services.gemini.paid_key')] as $name => $key) {
            $this->line("{$name} key: ".(is_string($key) && $key !== '' ? 'set' : 'missing'));
            if (! is_string($key) || $key === '') {
                continue;
            }

            $modelList = Http::connectTimeout(5)->timeout(25)->withHeaders(['x-goog-api-key' => $key])
                ->get(rtrim(config('ai.endpoint'), '/'));
            $available = collect($modelList->json('models', []))->pluck('name')->filter()->implode(', ');
            $this->line("{$name} available models: ".($available !== '' ? $available : 'unavailable (HTTP '.$modelList->status().')'));

            $model = (string) config('ai.models.standard');
            $base = rtrim(config('ai.endpoint'), '/').'/'.rawurlencode($model).':generateContent';
            $headers = ['x-goog-api-key' => $key];
            $test = Http::connectTimeout(5)->timeout(25)->withHeaders($headers)->post($base, [
                'contents' => [['role' => 'user', 'parts' => [['text' => 'Reply with JSON: {"ok":true}']]]],
                'generationConfig' => ['responseMimeType' => 'application/json'],
            ]);
            $this->line("{$name} test HTTP status: ".$test->status());
            $limits = collect($test->headers())->filter(fn ($value, $header) => str_contains(strtolower($header), 'rate') || str_contains(strtolower($header), 'quota'))->map(fn ($value, $header) => $header.': '.implode(', ', $value))->implode('; ');
            $this->line("{$name} rate limits: ".($limits !== '' ? $limits : 'not exposed by response headers'));

            $grounding = Http::connectTimeout(5)->timeout(25)->withHeaders($headers)->post($base, [
                'contents' => [['role' => 'user', 'parts' => [['text' => 'Find one example movie scene video.']]]],
                'tools' => [['google_search' => (object) []]],
            ]);
            $this->line("{$name} grounding accepted: ".($grounding->successful() ? 'yes' : 'no (HTTP '.$grounding->status().')'));
        }

        return self::SUCCESS;
    }
}

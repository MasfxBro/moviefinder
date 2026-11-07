<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\GroundedLlmClient;
use App\Contracts\LlmClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient implements GroundedLlmClient, LlmClient
{
    public function grounded(array $messages, string $model): array
    {
        $key = config('services.gemini.paid_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Gemini paid key is not configured.');
        }
        if ($model === '') {
            throw new RuntimeException('Gemini model is not configured.');
        }

        $response = Http::connectTimeout(5)->timeout(25)->retry([250], 1, fn ($exception, $request) => $exception instanceof RequestException && ($exception->response->status() === 429 || $exception->response->serverError()))
            ->withHeaders(['x-goog-api-key' => $key])
            ->post(rtrim(config('ai.endpoint'), '/').'/'.rawurlencode($model).':generateContent', [
                'contents' => $messages,
                'tools' => [['google_search' => new \stdClass]],
                'safetySettings' => [
                    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini grounded request failed with HTTP '.$response->status().'.');
        }

        return $response->json() ?? [];
    }

    public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array
    {
        $key = strtolower($plan) === 'free' ? config('services.gemini.free_key') : config('services.gemini.paid_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Gemini key is not configured.');
        }

        $model = $model !== '' ? $model : config('ai.models.standard');
        if (! is_string($model) || $model === '') {
            throw new RuntimeException('Gemini model is not configured.');
        }

        $response = Http::connectTimeout(5)->timeout(25)->retry([250], 1, fn ($exception, $request) => $exception instanceof RequestException && ($exception->response->status() === 429 || $exception->response->serverError()))
            ->withHeaders(['x-goog-api-key' => $key])
            ->post(rtrim(config('ai.endpoint'), '/').'/'.rawurlencode($model).':generateContent', [
                'contents' => $messages,
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $schema,
                ],
                'safetySettings' => [
                    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed with HTTP '.$response->status().'.');
        }

        $content = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($content)) {
            throw new RuntimeException('Gemini returned no structured content.');
        }

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Gemini returned invalid JSON.', previous: $exception);
        }
    }
}

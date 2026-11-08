<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AiMessageRequest;
use App\Http\Resources\AiMessageResource;
use App\Services\Ai\AiAccessState;
use App\Services\Ai\AiSearchService;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiController extends Controller
{
    public function messages(AiMessageRequest $request, AiSearchService $search): AiMessageResource|JsonResponse
    {
        if (! app(Settings::class)->get('features.ai_enabled', true)) {
            return response()->json([
                'reply' => 'AI search is under maintenance. Normal search still works. Please try again later.',
                'titles' => [],
                'clips' => [],
                'fallback_links' => [],
                'quota' => app(AiAccessState::class)->for($request->user()),
            ], 200);
        }

        try {
            return new AiMessageResource($search->search(
                $request->user(),
                $request->validated('message'),
                $request->validated('conversation_id'),
                $request->validated('mode'),
                $request->validated('title_id') !== null ? (int) $request->validated('title_id') : null,
            ));
        } catch (RuntimeException $exception) {
            $state = app(AiAccessState::class)->for($request->user());
            $now = CarbonImmutable::now(config('app.business_timezone', 'Asia/Jakarta'));
            $hours = max(1, (int) ceil($now->diffInMinutes($now->endOfDay()) / 60));
            $proLimit = $state['pro_limit'] ?? 12;

            $message = match ($exception->getMessage()) {
                'Free AI places are full.' => "Today's free AI places are full. The next free places open at ".(isset($state['next_wave_at']) ? CarbonImmutable::parse($state['next_wave_at'])->format('H:i') : '00:00').' WIB.',
                'AI allowance used.' => 'You have used this period\'s AI allowance. It renews on '.(isset($state['renews_at']) ? CarbonImmutable::parse($state['renews_at'])->format('j M Y') : 'the next period').'.',
                'Daily AI quota reached.' => match ($state['plan'] ?? 'free') {
                    'pro' => "You have reached today's limit. It resets at 00:00 WIB (in about {$hours} hours).",
                    'plus' => "You have used today's AI searches. They reset at 00:00 WIB (in about {$hours} hours). Pro offers {$proLimit} a day, including Smart searches.",
                    default => "You have used today's AI searches. They reset at 00:00 WIB (in about {$hours} hours).",
                },
                'AI is at capacity right now.' => 'AI is at capacity right now, try again later.',
                default => 'AI search is temporarily unavailable. Normal search still works. Please try again in a few minutes.',
            };

            return response()->json(['reply' => $message, 'titles' => [], 'clips' => [], 'fallback_links' => [], 'quota' => $state], 200);
        }
    }

    public function status(AiAccessState $state): JsonResponse
    {
        return response()->json(['quota' => $state->for(request()->user())]);
    }
}

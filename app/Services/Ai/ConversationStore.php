<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\PlanResolver;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ConversationStore
{
    private const TTL_SECONDS = 7200;

    public function __construct(private readonly PlanCatalog $plans, private readonly PlanResolver $resolver) {}

    /** @return array{conversation_id: string, history: array<int, array{role: string, content: string}>} */
    public function load(User $user, ?string $conversationId): array
    {
        $conversationId ??= (string) Str::uuid();
        abort_unless(Str::isUuid($conversationId), 422, 'Invalid conversation ID.');
        $ownerKey = 'ai:conv-owner:'.$conversationId;
        $owner = Cache::get($ownerKey);
        if ($owner !== null && $owner !== $user->id) {
            throw new AccessDeniedHttpException;
        }
        Cache::put($ownerKey, $user->id, self::TTL_SECONDS);
        $key = $this->key($user, $conversationId);
        $conversation = Cache::get($key);

        if ($conversation !== null && $conversation['user_id'] !== $user->id) {
            throw new AccessDeniedHttpException;
        }

        Cache::put($key, $conversation ?? ['user_id' => $user->id, 'history' => []], self::TTL_SECONDS);

        return ['conversation_id' => $conversationId, 'history' => $conversation['history'] ?? []];
    }

    /** @param array<int, array{role: string, content: string}> $history */
    public function save(User $user, string $conversationId, array $history): void
    {
        abort_unless(Str::isUuid($conversationId), 422, 'Invalid conversation ID.');
        $ownerKey = 'ai:conv-owner:'.$conversationId;
        $owner = Cache::get($ownerKey);
        if ($owner !== null && $owner !== $user->id) {
            throw new AccessDeniedHttpException;
        }
        Cache::put($ownerKey, $user->id, self::TTL_SECONDS);
        $key = $this->key($user, $conversationId);
        $conversation = Cache::get($key);

        if ($conversation !== null && $conversation['user_id'] !== $user->id) {
            throw new AccessDeniedHttpException;
        }

        $limit = (int) $this->plans->get($this->resolver->resolve($user))['conversation_turns'];
        Cache::put($key, ['user_id' => $user->id, 'history' => array_slice($history, -$limit)], self::TTL_SECONDS);
    }

    private function key(User $user, string $conversationId): string
    {
        return "ai:conv:{$user->id}:{$conversationId}";
    }
}

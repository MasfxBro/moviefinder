<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Watchlist;

class WatchlistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function view(User $user, Watchlist $watchlist): bool
    {
        return $user->id === $watchlist->user_id;
    }

    public function delete(User $user, Watchlist $watchlist): bool
    {
        return $user->id === $watchlist->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function toggle(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }
}

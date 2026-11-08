<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SearchHistory;
use App\Models\User;

class SearchHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SearchHistory $history): bool
    {
        return $user->id === $history->user_id;
    }

    public function delete(User $user, SearchHistory $history): bool
    {
        return $user->id === $history->user_id;
    }

    public function clear(User $user): bool
    {
        return true;
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminUserPolicy
{
    use HandlesAuthorization;

    public function view(User $admin, User $target)
    {
        return $admin->isAdmin() && $admin->id !== $target->id;
    }

    public function viewWatchlist(User $admin, User $target)
    {
        // Admin must not see other users' watchlist/history
        return false;
    }

    public function viewHistory(User $admin, User $target)
    {
        return false;
    }
}

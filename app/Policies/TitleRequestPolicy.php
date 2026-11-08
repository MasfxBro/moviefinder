<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TitleRequest;
use App\Models\User;

class TitleRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function view(User $user, TitleRequest $titleRequest): bool
    {
        return $user->id === $titleRequest->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, TitleRequest $titleRequest): bool
    {
        return $user->id === $titleRequest->user_id;
    }

    public function delete(User $user, TitleRequest $titleRequest): bool
    {
        return $user->id === $titleRequest->user_id;
    }
}

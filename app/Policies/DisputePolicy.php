<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        return $user->id === $dispute->user_id || $user->isAdmin();
    }

    public function reply(User $user, Dispute $dispute): bool
    {
        return $user->id === $dispute->user_id || $user->isAdmin();
    }
}

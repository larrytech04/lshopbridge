<?php

namespace App\Policies;

use App\Models\FundingRequest;
use App\Models\User;

class FundingRequestPolicy
{
    public function view(User $user, FundingRequest $funding): bool
    {
        return $user->id === $funding->user_id || $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\BeneficiaryAccount;
use App\Models\User;

class BeneficiaryAccountPolicy
{
    public function update(User $user, BeneficiaryAccount $account): bool
    {
        return $user->id === $account->user_id;
    }

    public function delete(User $user, BeneficiaryAccount $account): bool
    {
        return $user->id === $account->user_id;
    }
}

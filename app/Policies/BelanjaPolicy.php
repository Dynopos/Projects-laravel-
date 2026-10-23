<?php

namespace App\Policies;

use App\Models\Belanja;
use App\Models\User;

class BelanjaPolicy
{
    public function update(User $user, Belanja $belanja): bool
    {
        return $user->can('update', $belanja->projek);
    }

    public function delete(User $user, Belanja $belanja): bool
    {
        return $user->can('update', $belanja->projek);
    }
}

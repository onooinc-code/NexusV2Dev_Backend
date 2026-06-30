<?php

namespace App\Policies;

use App\Models\HedrasoulMessage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HedrasoulMessagePolicy
{
    use HandlesAuthorization;

    public function view(User $user, HedrasoulMessage $message): bool
    {
        return $user->id === $message->session?->user_id;
    }

    public function update(User $user, HedrasoulMessage $message): bool
    {
        return $user->id === $message->session?->user_id;
    }
}

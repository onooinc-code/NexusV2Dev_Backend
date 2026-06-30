<?php

namespace App\Policies;

use App\Models\HedrasoulSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HedrasoulSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, HedrasoulSession $session)
    {
        return $user->id === $session->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, HedrasoulSession $session)
    {
        return $user->id === $session->user_id;
    }

    public function delete(User $user, HedrasoulSession $session)
    {
        return $user->id === $session->user_id;
    }
}

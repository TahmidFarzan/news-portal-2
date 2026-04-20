<?php

namespace App\Observers;

use App\Models\User;
use App\Jobs\DeleteUserRelationsJob;

class UserObserver
{
    public function deleting(User $user): void
    {
        DeleteUserRelationsJob::dispatchSync($user->id);
    }
}


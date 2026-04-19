<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->activityLogs()->count() > 0) {
            $user->activityLogs()->delete();
        }
    }
}

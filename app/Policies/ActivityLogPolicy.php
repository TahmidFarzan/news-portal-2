<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\Response;

class ActivityLogPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, ActivityLog $activityLog): Response
    {
        return Response::allow();
    }

    public function delete(User $authUser, ActivityLog $activityLog): Response
    {
        if( $authUser->hasUserRole("Admin")){
            return Response::allow();
        }
        return Response::deny();
    }
}

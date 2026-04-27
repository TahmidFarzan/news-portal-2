<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Trend;
use Illuminate\Auth\Access\Response;
use App\Helpers\SystemHelper;

class TrendPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Trend $trend): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Trend $trend): Response
    {
        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $trend->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Trend $trend): Response
    {
        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $trend->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

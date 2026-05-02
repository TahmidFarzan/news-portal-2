<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contributor;
use Illuminate\Auth\Access\Response;
use App\Helpers\UserHelper;

class ContributorPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Contributor $contributor): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Contributor $contributor): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $contributor->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Contributor $contributor): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $contributor->created_by_id)) {
            return Response::allow();
        }
        return Response::deny();
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\News;
use Illuminate\Auth\Access\Response;
use App\Helpers\UserHelper;

class NewsPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, News $news): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, News $news): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, News $news): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK)) {
            return Response::allow();
        }
        return Response::deny();
    }
}

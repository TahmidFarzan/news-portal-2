<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Auth\Access\Response;
use App\Helpers\SystemHelper;

class TagPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Tag $tag): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Tag $tag): Response
    {
        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $tag->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Tag $tag): Response
    {

        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $tag->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

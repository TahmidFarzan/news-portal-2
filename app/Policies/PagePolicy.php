<?php
namespace App\Policies;

use App\Helpers\UserHelper;
use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PagePolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Page $page): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Page $page): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $page->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Page $page): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK)) {
            return Response::allow();
        }
        return Response::deny();
    }

    public function restore(User $authUser, Page $page): Response
    {
        if ($page->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK)) {
            return Response::allow();
        }
        return Response::deny();
    }

    public function forceDelete(User $authUser, Page $page): Response
    {
        if ($page->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $page->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

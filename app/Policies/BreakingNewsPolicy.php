<?php
namespace App\Policies;

use App\Helpers\UserHelper;
use App\Models\BreakingNews;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BreakingNewsPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, BreakingNews $breakingNews): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, BreakingNews $breakingNews): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $breakingNews->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $authUser, BreakingNews $breakingNews): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $breakingNews->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, BreakingNews $breakingNews): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $breakingNews->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $authUser, BreakingNews $breakingNews): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN) && ($breakingNews->is_published == false)) {
            return Response::allow();
        }

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_NEWS_DESK) && ($authUser->id === $breakingNews->created_by_id) && ($breakingNews->is_published == false)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

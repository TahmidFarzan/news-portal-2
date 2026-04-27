<?php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Helpers\SystemHelper;

class UserPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, User $user): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, User $user): Response
    {
        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $authUser, User $user): Response
    {
        if ($user->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, User $user): Response
    {
        if ($user->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $authUser, User $user): Response
    {
        if ($user->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserRole(SystemHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        if ($authUser->systemHelper::USER_ROLE_NEWS_DESK && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

}

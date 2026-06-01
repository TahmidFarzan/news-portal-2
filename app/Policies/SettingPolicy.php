<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Access\Response;
use App\Helpers\UserHelper;

class SettingPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Setting $setting): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Setting $setting): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Setting $setting): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

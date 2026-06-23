<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GoogleAdsence;
use App\Helpers\UserHelper;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class GoogleAdsencePolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, GoogleAdsence $googleAdsence): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $authUser, GoogleAdsence $googleAdsence): Response
    {
        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, GoogleAdsence $googleAdsence): Response
    {

        if ($authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

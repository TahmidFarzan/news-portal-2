<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Location;
use Illuminate\Auth\Access\Response;

class LocationPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Location $location): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Location $location): Response
    {
        if ($authUser->hasUserRole("Admin")) {
            return Response::allow();
        }

        if ($authUser->hasUserRole("News desk") && ($authUser->id === $location->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Location $location): Response
    {

        if ($authUser->hasUserRole("Admin")) {
            return Response::allow();
        }

        if ($authUser->hasUserRole("News desk") && ($authUser->id === $location->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

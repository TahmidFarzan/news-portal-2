<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Event $event): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Event $event): Response
    {
        if ($authUser->hasUserRole("Admin")) {
            return Response::allow();
        }

        if ($authUser->hasUserRole("News desk") && ($authUser->id === $event->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Event $event): Response
    {

        if ($authUser->hasUserRole("Admin")) {
            return Response::allow();
        }

        if ($authUser->hasUserRole("News desk") && ($authUser->id === $event->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

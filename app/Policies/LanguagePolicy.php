<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Language;
use Illuminate\Auth\Access\Response;

class LanguagePolicy
{
    public function viewAny(User $authUser): Response
    {
        return Response::allow();
    }

    public function view(User $authUser, Language $language): Response
    {
        return Response::allow();
    }

    public function create(User $authUser): Response
    {
        return Response::allow();
    }

    public function update(User $authUser, Language $language): Response
    {
        if ($authUser->hasUserRole("Admin")) {
            return Response::allow();
        }

        if ($authUser->hasUserRole("News desk") && ($authUser->id === $language->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Language $language): Response
    {

        if ($authUser->hasUserRole("Admin")) {
            return Response::allow();
        }

        if ($authUser->hasUserRole("News desk") && ($authUser->id === $language->created_by_id)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

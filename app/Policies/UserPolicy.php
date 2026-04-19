<?php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        if( $authUser->hasUserRole("Admin")){
            return Response::allow();
        }

        if( $authUser->hasUserRole("Supervisor") && $user->hasUserRole("Member") && ($authUser->id === $user->supervisor_id)){
            return Response::allow();
        }

        if ($authUser->hasUserRole("Member") && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $authUser, User $user): Response
    {
        if ($user->is_default) {
            return Response::deny();
        }

        if( $authUser->hasUserRole("Admin")){
            return Response::allow();
        }

        if( $authUser->hasUserRole("Supervisor") && $user->hasUserRole("Member") && ($authUser->id === $user->supervisor_id)){
            return Response::allow();
        }

        if ($authUser->hasUserRole("Member") && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, User $user): Response
    {
        if ($user->is_default) {
            return Response::deny();
        }

        if( $authUser->hasUserRole("Admin")){
            return Response::allow();
        }

        if( $authUser->hasUserRole("Supervisor") && $user->hasUserRole("Member") && ($authUser->id === $user->supervisor_id)){
            return Response::allow();
        }

        if ($authUser->hasUserRole("Member") && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $authUser, User $user): Response
    {
        if ($user->is_default) {
            return Response::deny();
        }

        if( $authUser->hasUserRole("Admin")){
            return Response::allow();
        }

        if( $authUser->hasUserRole("Supervisor") && $user->hasUserRole("Member") && ($authUser->id === $user->supervisor_id)){
            return Response::allow();
        }

        if ($authUser->hasUserRole("Member") && ($authUser->id === $user->id)) {
            return Response::allow();
        }

        return Response::deny();
    }

}

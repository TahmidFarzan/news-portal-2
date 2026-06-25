<?php
namespace App\Policies;

use App\Helpers\UserPermissionHelper;
use App\Models\BreakingNews;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BreakingNewsPolicy
{
    public function before(User $authUser, string $ability): bool | null
    {
        if ($authUser->is_super_admin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $authUser): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_VIEW_ANY;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function view(User $authUser, BreakingNews $breakingNews): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_VIEW;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function create(User $authUser): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_CREATE;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function update(User $authUser, BreakingNews $breakingNews): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_UPDATE;

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();

        }

        return Response::deny();
    }

    public function restore(User $authUser, BreakingNews $breakingNews): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_RESTORE;

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, BreakingNews $breakingNews): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_DELETE;

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $authUser, BreakingNews $breakingNews): Response
    {
        $module = UserPermissionHelper::MODULE_BREAKING_NEWS;
        $access = UserPermissionHelper::ACCESS_FORCE_DELETE;

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

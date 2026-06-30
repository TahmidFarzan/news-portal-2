<?php
namespace App\Policies;

use App\Helpers\UserPermissionHelper;
use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PagePolicy
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
        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_VIEW_ANY;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function view(User $authUser, Page $page): Response
    {
        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_VIEW;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function create(User $authUser): Response
    {
        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_CREATE;

        return $authUser->hasUserPermission($module, $access) ? Response::allow() : Response::deny();
    }

    public function update(User $authUser, Page $page): Response
    {
        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_UPDATE;

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $authUser, Page $page): Response
    {
        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_DELETE;

        if ($page->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $authUser, Page $page): Response
    {

        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_RESTORE;

        if ($page->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $authUser, Page $page): Response
    {
        $module = UserPermissionHelper::MODULE_PAGE;
        $access = UserPermissionHelper::ACCESS_FORCE_DELETE;

        if ($page->is_published) {
            return Response::deny();
        }

        if ($page->is_default) {
            return Response::deny();
        }

        if ($authUser->hasUserPermission($module, $access)) {
            return Response::allow();
        }

        return Response::deny();
    }
}

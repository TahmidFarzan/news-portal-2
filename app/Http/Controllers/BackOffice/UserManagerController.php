<?php

namespace App\Http\Controllers\BackOffice;

use App\Services\BackOffice\UserManagementService;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserManagerController extends Controller
{
    protected UserManagementService $service;

    public function __construct(UserManagementService $service)
    {
        $this->service = $service;
        $this->middleware(['auth', 'verified', 'user.role:admin,supervisor']);
    }

    public function index()
    {
        return Inertia::render('back-office/user-manager/Index');
    }

    public function userIndex(Request $request)
    {
        $user = $this->service->new();
        Gate::authorize('viewAny', $user);

        $users = $this->service->search($request);

        return Inertia::render('back-office/user-manager/users/Index', [
            'users' => $users,
        ]);
    }

    public function userDetails(string $slug)
    {
        $user = $this->service->findBySlug($slug);
        $user = $this->service->loadRelations($user);

        Gate::authorize('create', $user);

        return Inertia::render('back-office/user-manager/users/Details', [
            'user' => $user,
        ]);
    }

    public function userCreate()
    {
        $user = $this->service->new();
        Gate::authorize('create', $user);

        return Inertia::render('back-office/user-manager/users/Create', [
            'user' => $user,
        ]);
    }

    public function userEdit(string $slug)
    {
        $user = $this->service->findBySlug($slug);
        $user = $this->service->loadRelations($user);

        Gate::authorize('update', $user);

        return Inertia::render('back-office/user-manager/users/Create', [
            'user' => $user,
        ]);
    }

    public function userSave(UserRequest $request)
    {
        $user = $this->service->new();
        Gate::authorize('create', $user);

        $result = $this->service->save($request, $user);

        return to_route('back-office.user-manager.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userUpdate(UserRequest $request, string $slug)
    {
        $user = $this->service->findBySlug($slug);

        Gate::authorize('update', $user);

        $result = $this->service->save($request, $user);

        return to_route('back-office.user-manager.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userActive(string $slug)
    {
        $user = $this->service->findTrashedBySlug($slug);

        Gate::authorize('restore', $user);

        $result = $this->service->activate($user);

        return to_route('back-office.user-manager.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userInactive(string $slug)
    {
        $user = $this->service->findBySlug($slug);

        Gate::authorize('delete', $user);

        $result = $this->service->deactivate($user);

        return to_route('back-office.user-manager.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userDelete(string $slug)
    {
        $user = $this->service->findWithTrashedBySlug($slug);

        Gate::authorize('forceDelete', $user);

        $result = $this->service->delete($user);

        return to_route('back-office.user-manager.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }
}

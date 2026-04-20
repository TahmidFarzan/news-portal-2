<?php

namespace App\Http\Controllers\BackOffice;

use App\Services\BackOffice\UserService;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware(['auth', 'verified', 'user.role:admin,supervisor']);
    }

    public function userIndex(Request $request)
    {
        $user = $this->userService->new();
        Gate::authorize('viewAny', $user);

        $users = $this->userService->search($request);

        return Inertia::render('back-office/users/Index', [
            'users' => $users,
        ]);
    }

    public function userDetails(string $slug)
    {
        $user = $this->userService->findBySlug($slug);
        $user = $this->userService->loadRelations($user);

        Gate::authorize('create', $user);

        return Inertia::render('back-office/users/Details', [
            'user' => $user,
        ]);
    }

    public function userCreate()
    {
        $user = $this->userService->new();
        Gate::authorize('create', $user);

        return Inertia::render('back-office/users/Create', [
            'user' => $user,
        ]);
    }

    public function userEdit(string $slug)
    {
        $user = $this->userService->findBySlug($slug);
        $user = $this->userService->loadRelations($user);

        Gate::authorize('update', $user);

        return Inertia::render('back-office/users/Create', [
            'user' => $user,
        ]);
    }

    public function userSave(UserRequest $request)
    {
        $user = $this->userService->new();
        Gate::authorize('create', $user);

        $result = $this->userService->save($request, $user);

        return to_route('back-office.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userUpdate(UserRequest $request, string $slug)
    {
        $user = $this->userService->findBySlug($slug);

        Gate::authorize('update', $user);

        $result = $this->userService->save($request, $user);

        return to_route('back-office.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userActive(string $slug)
    {
        $user = $this->userService->findTrashedBySlug($slug);

        Gate::authorize('restore', $user);

        $result = $this->userService->activate($user);

        return to_route('back-office.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userInactive(string $slug)
    {
        $user = $this->userService->findBySlug($slug);

        Gate::authorize('delete', $user);

        $result = $this->userService->deactivate($user);

        return to_route('back-office.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function userDelete(string $slug)
    {
        $user = $this->userService->findWithTrashedBySlug($slug);

        Gate::authorize('forceDelete', $user);

        $result = $this->userService->delete($user);

        return to_route('back-office.users.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }
}

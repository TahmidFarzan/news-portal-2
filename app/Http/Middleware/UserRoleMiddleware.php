<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$userRoles): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && $user->hasUserRole($userRoles)) {
            return $next($request);
        }

        return to_route('auth-user.dashboard.index')->with('flash_message', [
            'message' => "warning",
            'status'  => __('You are not authorized to access.'),
        ]);
    }
}

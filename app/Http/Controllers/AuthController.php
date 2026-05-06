<?php
namespace App\Http\Controllers;

use App\Http\Requests\AuthUserAccountRequest;
use App\Http\Requests\AuthUserProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AuthService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected DashboardService $dashboardService;

    public function __construct(AuthService $authService, DashboardService $dashboardService)
    {
        $this->middleware('guest')->only([
            'login', 'register', 'forgotPassword', 'resetPassword',
            'loginForm', 'registerForm', 'forgetPasswordForm', 'resetPasswordForm',
        ]);

        $this->middleware('auth')->only([
            'logout', 'emailVerificationResend', 'dashboard',
            'profileIndex', 'accountIndex', 'profileUpdate', 'accountUpdate',
        ]);

        $this->middleware(['auth', 'signed'])->only(['emailVerification']);
        $this->middleware('throttle:3,1')->only(['login']);

        $this->authService      = $authService;
        $this->dashboardService = $dashboardService;
    }

    public function loginForm()
    {
        return Inertia::render('auth/Login');
    }

    public function registerForm()
    {
        abort(401, __('form-requests.auth.register.is_not_allow'));
    }

    public function forgetPasswordForm()
    {
        return Inertia::render('auth/ForgotPassword');
    }

    public function resetPasswordForm($token, $email)
    {
        return Inertia::render('auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function emailVerificationNotice()
    {
        return Inertia::render('auth/EmailVerification');
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request);

        if ($result['status'] === 'success') {
            $redirect = session()->pull('url.intended', route('auth-user.dashboard.index'));

            return redirect($redirect)->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
            ]);
        }

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        abort(401, __('form-requests.auth.register.is_not_allow'));
    }

    public function logout()
    {
        $result = $this->authService->logout();

        return to_route('login')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $result = $this->authService->forgotPassword($request);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, string $token, string $email)
    {
        $result = $this->authService->resetPassword($request, $token, $email);

        return to_route('login')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function emailVerification(Request $request, $id, $hash)
    {
        $result = $this->authService->emailVerification($request, $id, $hash);

        $route = $result['status'] === 'success'
            ? 'auth-user.dashboard.index'
            : 'login';

        return to_route($route)->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function emailVerificationResend(Request $request)
    {
        $result = $this->authService->emailVerificationResend($request);

        return back()->with('status', $result['message']);
    }

    public function profileIndex()
    {
        $user = $this->authService->authUser();
        $user = $this->authService->loadRelations($user);

        return Inertia::render('auth-user/Profile', ['user' => $user]);
    }

    public function accountIndex()
    {
        $user = $this->authService->authUser();
        $user = $this->authService->loadRelations($user);

        return Inertia::render('auth-user/Account', ['user' => $user]);
    }

    public function dashboard()
    {

        return Inertia::render('auth-user/Dashboard');
    }

    public function profileUpdate(AuthUserProfileRequest $request)
    {
        $user   = $this->authService->authUser();
        $result = $this->authService->profileUpdate($request, $user);

        return to_route('auth-user.profile.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function accountUpdate(AuthUserAccountRequest $request)
    {
        $user   = $this->authService->authUser();
        $result = $this->authService->accountUpdate($request, $user);

        return to_route('auth-user.account.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}

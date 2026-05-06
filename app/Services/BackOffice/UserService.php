<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function new (): User
    {
        return new User();
    }

    public function findBySlug(string $slug): User
    {
        return User::where('slug', $slug)->firstOrFail();
    }

    public function findTrashedBySlug(string $slug): User
    {
        return User::onlyTrashed()->where('slug', $slug)->firstOrFail();
    }

    public function findWithTrashedBySlug(string $slug): User
    {
        return User::withTrashed()->where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(User $user): User
    {
        $user->load([
            'userRole',
            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $user;
    }

    public function search(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $perPage = $request->input('per_page', 10);

        $query = User::withTrashed()->with("userRole");

        if ($request->filled('is_active') && $request->boolean('is_active') == true) {
            $query->whereNull('deleted_at');
        }

        if ($request->filled('is_active') && $request->boolean('is_active') == false) {
            $query->whereNotNull('deleted_at');
        }

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('user_role_id')) {
            $query->where('user_role_id', $request->input('user_role_id'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(UserRequest $request, User $user): array
    {
        DB::beginTransaction();

        try {
            $isNew = empty($user->id);

            $user->name           = $request->input('name');
            $user->email          = $request->input('email');
            $user->gender         = $request->input('gender');
            $user->mobile         = $request->input('mobile');
            $user->address        = $request->input('address');
            $user->religion       = $request->input('religion');
            $user->birth_date     = $request->input('birth_date');
            $user->marital_status = $request->input('marital_status');

            $user->is_default   = false;
            $user->user_role_id = $request->input('user_role_id');

            $user->password          = Hash::make($request->password);
            $user->created_by_id     = $isNew ? Auth::id() : $user->created_by_id;
            $user->email_verified_at = $request->boolean('set_as_verify_email') ? now() : null;

            $user->save();

            self::saveProfileImage($request, $user);

            DB::commit();

            if (! $request->boolean('set_as_verify_email') && ! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }

            return [
                'status'  => 'success',
                'message' => __('status-messages.user.save.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('User save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.user.save.failed'),
            ];
        }
    }

    public function activate(User $user): array
    {
        DB::beginTransaction();

        try {
            $user->restore();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.user.active.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('User activate failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.user.active.failed'),
            ];
        }
    }

    public function deactivate(User $user): array
    {
        DB::beginTransaction();

        try {
            $user->delete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.user.inactive.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('User deactivate failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.user.inactive.failed'),
            ];
        }
    }

    public function delete(User $user): array
    {
        DB::beginTransaction();

        try {
            $user->forceDelete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.user.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('User delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.user.delete.failed'),
            ];
        }
    }

    private static function saveProfileImage(UserRequest $request, User $user)
    {
        if ($request->hasFile('upload_feature_image_mobile_image')) {
            self::deleteExistingProfileImage($user);

            $file      = $request->file('upload_feature_image_mobile_image');
            $extension = $file->getUserOriginalExtension();
            $fileName  = MediaHelper::generateMediaName($user->name, $extension, 200);

            $user->addMedia($file)
                ->usingFileName($fileName)
                ->usingName($user->name)
                ->withCustomProperties([
                    'alt'     => $user->name,
                    'caption' => $user->name,
                    'role'    => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE,
                ])
                ->toMediaCollection($user->media_collection_name);
        }

        if ($request->input('media_selected_feature_image_mobile_image_url')) {
            self::deleteExistingProfileImage($user);

            $url       = $request->input('media_selected_feature_image_mobile_image_url');
            $extension = pathinfo($url, PATHINFO_EXTENSION);
            $fileName  = MediaHelper::generateMediaName($user->name, $extension, 200);

            $user->addMediaFromUrl($url)
                ->usingName($user->name)
                ->usingFileName($fileName)
                ->withCustomProperties([
                    'caption' => $user->name,
                    'alt'     => $user->name,
                    'role'    => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE,
                ])
                ->toMediaCollection($user->media_collection_name);
        }
    }

    private static function deleteExistingProfileImage(User $user)
    {
        $existing = $user->profileImage();

        if ($existing) {
            $existing->delete();
        }
    }
}

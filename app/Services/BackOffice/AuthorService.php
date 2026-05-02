<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthorService
{
    public function new (): Author
    {
        return new Author();
    }

    public function find(string $slug): Author
    {
        return Author::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Author $author): Author
    {
        $author->load([
            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $author;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Author::query();

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
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
                    ->orWhere('brief', 'like', "%{$search}%")
                    ->orWhere('seo_brief', 'like', '%' . $search . '%')
                    ->orWhere('seo_title', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(AuthorRequest $request, Author $author): array
    {
        DB::beginTransaction();

        try {
            $isNew       = empty($author->id);
            $statusEvent = $isNew ? "save" : "update";

            $seoKeywords = null;

            if ($request->input('seo_keywords')) {
                $seoKeywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords'));
            }

            $author->name            = $request->input('name');
            $author->brief           = $request->input('brief');
            $author->profile_details = $request->input('profile_details');
            $author->language_id     = $request->input('language_id');
            $author->seo_title       = $request->input('seo_title', $request->input('name'));
            $author->seo_brief       = $request->input('seo_brief', $request->input('brief'));
            $author->seo_keywords    = $seoKeywords;
            $author->created_by_id   = $isNew ? Auth::id() : $author->created_by_id;

            self::saveProfileImage($request, $author);

            $author->save();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __("status-messages.author.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error("Failed to {$statusEvent} author.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.author.save.failed'),
            ];
        }
    }

    public function delete(Author $author): array
    {
        DB::beginTransaction();

        try {
            $author->delete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.author.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('Author delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.author.delete.failed'),
            ];
        }
    }

    private static function saveProfileImage(AuthorRequest $request, Author $author)
    {
        if (! $request->hasFile('profile_image')) {
            return;
        }

        $existing = $author->getProfileImageAttribute();
        if ($existing) {
            $existing->delete();
        }

        $uploaded = $request->file('profile_image');

        if ($uploaded) {
            $name = MediaHelper::generateMediaName(
                $author->name,
                $uploaded->getClientOriginalExtension(),
                200
            );

            $author->addMedia($uploaded)
                ->usingFileName($name)
                ->withCustomProperties([
                    'alt'     => $user->name ?? null,
                    'caption' => $user->name ?? null,
                    'role'    => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE,
                ])
                ->toMediaCollection($author->media_collection_name);
        }
    }

}

<?php
namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Helpers\DatatableHelper;
use App\Helpers\EventHelper;
use App\Helpers\GoogleAdsenceHelper;
use App\Helpers\MediaHelper;
use App\Helpers\MenuHelper;
use App\Helpers\PageHelper;
use App\Helpers\UserHelper;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\MenuType;
use App\Models\Survey;
use App\Models\News;
use App\Models\NewsType;
use App\Models\Page;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SearchService
{
    public function perPages(Request $request): array
    {
        $options = DatatableHelper::perPages();

        if ($request->filled('search')) {
            $search  = strtolower($request->input('search'));
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function genders(Request $request): array
    {
        $options = UserHelper::genders();

        if ($request->filled('search')) {
            $search  = strtolower($request->input('search'));
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function religions(Request $request): array
    {
        $options = UserHelper::religions();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function maritalStatuses(Request $request): array
    {
        $options = UserHelper::maritalStatuses();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function activityLogEvents(Request $request): array
    {
        $options = ActivityLogHelper::activityLogEvents();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function activityLogSubjectTypes(Request $request): array
    {
        $options = ActivityLogHelper::activityLogSubjectTypes();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function pageSections(Request $request): array
    {
        $options = PageHelper::pageSections();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function menuItemModels(Request $request): array
    {
        $options = MenuHelper::menuItemModels();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function eventPositions(Request $request): array
    {
        $options = EventHelper::positions();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function googleAdsenceTypes(Request $request): array
    {
        $options = GoogleAdsenceHelper::types();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function googleAdsencePositions(Request $request): array
    {
        $options = GoogleAdsenceHelper::positions();

        if ($request->filled('search')) {
            $search  = $request->input('search');
            $options = $options->filter(
                fn($row) =>
                stripos((string) $row->id, $search) !== false ||
                stripos($row->name, $search) !== false
            );
        }

        $items = $options->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
        ]);

        return [
            'items'        => $items,
            'total'        => 1,
            'current_page' => 1,
            'last_page'    => 1,
        ];
    }

    public function users(Request $request): array
    {
        $query = User::query()
            ->whereNull('deleted_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'   => $user->id,
            'name' => $user->name,
            'slug' => $user->slug,

        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function userPermissions(Request $request): array
    {
        $query = UserPermission::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('module', 'like', "%{$search}%")
                    ->orWhere('access', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'     => $user->id,
            'module' => $user->module,
            'access' => $user->access,
            'name'   => $user->name,
        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function userPermissionsByGroup(Request $request): array
    {
        $query = UserPermission::query();

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('module', 'like', "%{$search}%")
                    ->orWhere('access', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereKeyNot($request->input('except_id'));
        }

        return $query
            ->orderBy('module')
            ->orderBy('access')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    public function newsTypes(Request $request): array
    {
        $query = NewsType::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'   => $user->id,
            'name' => $user->name,
        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function languages(Request $request): array
    {
        $query = Language::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function categories(Request $request): array
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('only_main') &&
            $request->boolean('only_main') &&
            ! $request->filled('parent_id')
        ) {
            $query->whereNull('parent_id');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function tags(Request $request): array
    {
        $query = Tag::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('only_trend') && $request->input('only_trend') == true) {
            $query->whereHas('trend');
        }

        if ($request->filled('except_trend') && $request->input('except_trend') == true) {
            $query->whereDoesntHave('trend');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function locations(Request $request): array
    {
        $query = Location::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('only_main') &&
            $request->boolean('only_main') &&
            ! $request->filled('parent_id')
        ) {
            $query->whereNull('parent_id');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function events(Request $request): array
    {
        $query = Event::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function contributors(Request $request): array
    {
        $query = Contributor::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function medias(Request $request): array
    {
        $query = Media::query();

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('custom_properties->alt', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('custom_properties->caption', 'like', '%' . $request->input('search') . '%')
            );
        }

        if ($request->filled('media_type') &&
            Str::lower($request->input('media_type')) !== 'all'
        ) {
            $query->where('mime_type', 'like', $request->input('media_type') . '%');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 50));

        $list = $records->map(fn($media) => [
            'id'                => $media->id,
            'name'              => $media->name,
            'uuid'              => $media->uuid,
            'mime_type'         => $media->mime_type,
            'custom_properties' => $media->custom_properties,
            'caption'           => $media->getCustomProperty('caption') ?? $media->model->name ?? "",
            'alt'               => $media->getCustomProperty('alt') ?? $media->model->name ?? "",
            'media_type'        => $media->getTypeFromMime(),
            'original_url'      => $media->original_url,
            'media_url'         => $media->hasGeneratedConversion(MediaHelper::DEFAULT_CONVERSION) ? $media->getUrl(MediaHelper::DEFAULT_CONVERSION) : $media->getUrl(),
            'media_srcset'      => $media->hasGeneratedConversion(MediaHelper::DEFAULT_CONVERSION) ? $media->getSrcset(MediaHelper::DEFAULT_CONVERSION) : $media->getSrcset(),
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function news(Request $request): array
    {
        $query = News::query();

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sub_title', 'like', "%{$search}%")
                    ->orWhere('content_shoulder', 'like', "%{$search}%")
                    ->orWhere('brief', 'like', "%{$search}%")
                    ->orWhere('seo_brief', 'like', '%' . $search . '%')
                    ->orWhere('seo_title', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('news_type_id')) {
            $query->where('news_type_id', $request->input('news_type_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        $records = $query->where('is_published', true)->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 50));

        $list = $records->map(fn($news) => [
            'id'                      => $news->id,
            'title'                   => $news->title,
            'sub_title'               => $news->sub_title,
            'content_shoulder'        => $news->content_shoulder,
            'slug'                    => $news->slug,
            'published_at'            => $news->title_with_published_at,
            'title_with_published_at' => $news->title_with_published_at,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function breakingNews(Request $request): array
    {
        $query = BreakingNews::query();

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_sync_to_news') && ! $request->boolean('is_sync_to_news')) {
            $query->whereNull("news_id");
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        $records = $query->where('is_published', true)->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 50));

        $list = $records->map(fn($news) => [
            'id'    => $news->id,
            'title' => $news->title,
            'slug'  => $news->slug,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function pages(Request $request): array
    {
        $query = Page::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('only_main') &&
            $request->boolean('only_main') &&
            ! $request->filled('parent_id')
        ) {
            $query->whereNull('parent_id');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function menuItems(Request $request): array
    {
        $query = MenuItem::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('only_main') &&
            $request->boolean('only_main') &&
            ! $request->filled('parent_id')
        ) {
            $query->whereNull('parent_id');
        }

        $records = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = $records->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
        ]);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function menuTypes(Request $request): array
    {
        $query = MenuType::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'   => $user->id,
            'name' => $user->name,
        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function surveys(Request $request): array
    {
        $query = Survey::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        $items = $records->map(fn($user) => [
            'id'   => $user->id,
            'name' => $user->name,
        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function categoryTree(Request $request): array
    {
        $query = Category::whereNull('parent_id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('parent_id')) {
            $parent = $this->category($request->input('parent_id'));
            $root   = self::rootCategoryParent($parent);
            $query->where('id', $root->id);
        }

        $records = $query->orderBy('id', 'asc')
            ->with(['children'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = self::formatCategoryTree($records->getCollection(), 0, null);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];
    }

    public function locationTree(Request $request): array
    {
        $query = Location::whereNull('parent_id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('parent_id')) {
            $parent = $this->location($request->input('parent_id'));
            $root   = self::rootLocationParent($parent);
            $query->where('id', $root->id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        $records = $query->orderBy('id', 'asc')
            ->with(['children'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = self::formatLocationTree($records->getCollection(), 0, null);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];
    }

    public function menuItemTree(Request $request): array
    {
        $query = MenuItem::whereNull('parent_id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('parent_id')) {
            $parent = $this->menuItem($request->input('parent_id'));
            $root   = self::rootMenuItemParent($parent);
            $query->where('id', $root->id);
        }

        $records = $query->orderBy('id', 'asc')
            ->with(['children'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = self::formatMenuItemTree($records->getCollection(), 0, null);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];
    }

    public function pageTree(Request $request): array
    {
        $query = Page::whereNull('parent_id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('parent_id')) {
            $parent = $this->page($request->input('parent_id'));
            $root   = self::rootPageParent($parent);
            $query->where('id', $root->id);
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        $records = $query->orderBy('id', 'asc')
            ->with(['children'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        $list = self::formatPageTree($records->getCollection(), 0, null);

        return [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];
    }

    public function user(int | string $slugOrId): User
    {
        return User::with("userPermission")->where('id', $slugOrId)->orWhere('slug', $slugOrId)->firstOrFail();
    }

    public function userPermission(int | string $slugOrId): UserPermission
    {
        return UserPermission::where('id', $slugOrId)->firstOrFail();
    }

    public function newsType(int | string $slugOrId): NewsType
    {
        return NewsType::where('id', $slugOrId)->firstOrFail();
    }

    public function language(int | string $slugOrId): Language
    {
        return Language::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function category(int | string $slugOrId): Category
    {
        return Category::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function tag(int | string $slugOrId): Tag
    {
        return Tag::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function location(int | string $slugOrId): Location
    {
        return Location::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function event(int | string $slugOrId): Event
    {
        return Event::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function contributor(int | string $slugOrId): Contributor
    {
        return Contributor::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function menuItem(int | string $slugOrId): MenuItem
    {
        return MenuItem::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function page(int | string $slugOrId): Page
    {
        return Page::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function survey(int | string $slugOrId): Page
    {
        return Survey::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    private static function rootCategoryParent(Category $record): Category
    {
        $parent = $record;

        if ($record->parent_id !== null) {
            $parent = $record->parent;

            if ($parent->parent_id !== null) {
                $parent = self::rootCategoryParent($parent);
            }
        }

        return $parent;
    }

    private static function rootLocationParent(Location $record): Location
    {
        $parent = $record;

        if ($record->parent_id !== null) {
            $parent = $record->parent;

            if ($parent->parent_id !== null) {
                $parent = self::rootLocationParent($parent);
            }
        }

        return $parent;
    }

    private static function rootMenuItemParent(MenuItem $record): MenuItem
    {
        $parent = $record;

        if ($record->parent_id !== null) {
            $parent = $record->parent;

            if ($parent->parent_id !== null) {
                $parent = self::rootMenuItemParent($parent);
            }
        }

        return $parent;
    }

    private static function rootPageParent(Page $record): Page
    {
        $parent = $record;

        if ($record->parent_id !== null) {
            $parent = $record->parent;

            if ($parent->parent_id !== null) {
                $parent = self::rootPageParent($parent);
            }
        }

        return $parent;
    }

    private static function formatCategoryTree(Collection $records, int $level = 0, ?array $visited = null): array
    {
        $visited ??= [];

        $list = [];

        foreach ($records as $record) {

            if (in_array($record->id, $visited, true)) {
                continue;
            }

            $visited[] = $record->id;

            $list[] = [
                'id'               => $record->id,
                'name'             => $record->name,
                'name_tree'        => $record->name_tree,
                'indentation_name' => $record->indentation_name,
            ];

            if (! empty($record->children) && $record->children->isNotEmpty()) {
                $list = array_merge(
                    $list,
                    self::formatCategoryTree($record->children, $level + 1, $visited)
                );
            }
        }

        return $list;
    }

    private static function formatLocationTree(Collection $records, int $level = 0, ?array $visited = null): array
    {
        $visited ??= [];

        $list = [];

        foreach ($records as $record) {

            if (in_array($record->id, $visited, true)) {
                continue;
            }

            $visited[] = $record->id;

            $list[] = [
                'id'               => $record->id,
                'name'             => $record->name,
                'name_tree'        => $record->name_tree,
                'indentation_name' => $record->indentation_name,
            ];

            if (! empty($record->children) && $record->children->isNotEmpty()) {
                $list = array_merge(
                    $list,
                    self::formatLocationTree($record->children, $level + 1, $visited)
                );
            }
        }

        return $list;
    }

    private static function formatMenuItemTree(Collection $records, int $level = 0, ?array $visited = null): array
    {
        $visited ??= [];

        $list = [];

        foreach ($records as $record) {

            if (in_array($record->id, $visited, true)) {
                continue;
            }

            $visited[] = $record->id;

            $list[] = [
                'id'               => $record->id,
                'name'             => $record->name,
                'name_tree'        => $record->name_tree,
                'indentation_name' => $record->indentation_name,
            ];

            if (! empty($record->children) && $record->children->isNotEmpty()) {
                $list = array_merge(
                    $list,
                    self::formatMenuItemTree($record->children, $level + 1, $visited)
                );
            }
        }

        return $list;
    }

    private static function formatPageTree(Collection $records, int $level = 0, ?array $visited = null): array
    {
        $visited ??= [];

        $list = [];

        foreach ($records as $record) {

            if (in_array($record->id, $visited, true)) {
                continue;
            }

            $visited[] = $record->id;

            $list[] = [
                'id'                => $record->id,
                'title'             => $record->title,
                'title_tree'        => $record->title_tree,
                'indentation_title' => $record->indentation_title,
            ];

            if (! empty($record->children) && $record->children->isNotEmpty()) {
                $list = array_merge(
                    $list,
                    self::formatPageTree($record->children, $level + 1, $visited)
                );
            }
        }

        return $list;
    }

}

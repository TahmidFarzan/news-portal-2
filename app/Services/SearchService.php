<?php
namespace App\Services;

use App\Helpers\SystemHelper;
use App\Helpers\UserHelper;
use App\Helpers\MediaHelper;
use App\Models\Author;
use App\Models\Category;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SearchService
{
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

    public function perPages(Request $request): array
    {
        $options = SystemHelper::perPages();

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
        $options = SystemHelper::activityLogEvents();

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
        $options = SystemHelper::activityLogSubjectTypes();

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
            'id'                  => $user->id,
            'name'                => $user->name,
            'slug'                => $user->slug,
            'name_with_user_role' => $user->name_with_user_role,
        ]);

        return [
            'items'        => $items,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ];
    }

    public function userRoles(Request $request): array
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $query = UserRole::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('except_id')) {
            $query->whereNot("id", $request->input('except_id'));
        }

        if ($authUser->hasUserRole("Supervisor")) {
            $query->where('name', "Member");
        }

        if ($authUser->hasUserRole("Member")) {
            $query->where('name', "Member");
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

    public function authors(Request $request): array
    {
        $query = Author::query();

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
            'id'              => $media->id,
            'name'            => $media->name,
            'uuid'            => $media->uuid,
            'media_mime_type' => $media->mime_type,
            'caption'         => $media->getCustomProperty('caption') ?? $media->model->name ?? "",
            'alt'             => $media->getCustomProperty('alt') ?? $media->model->name ?? "",
            'media_type'      => $media->getTypeFromMime(),
            'url'             => $media->getUrl(),
            'media_url'       => $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getUrl(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getUrl(),
            'media_srcset'    => $media->hasGeneratedConversion(MediaHelper::DEFAULT_MEDIA_CONVERSION) ? $media->getSrcset(MediaHelper::DEFAULT_MEDIA_CONVERSION) : $media->getSrcset(),
        ]);

        return [
            'items'        => $list,
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

    public function user(int | string $slugOrId): User
    {
        return User::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function userRole(int | string $slugOrId): UserRole
    {
        return UserRole::where('id', $slugOrId)->firstOrFail();
    }

    public function language($slugOrId): Language
    {
        return Language::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function category($slugOrId): Category
    {
        return Category::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function tag($slugOrId): Tag
    {
        return Tag::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function location($slugOrId): Location
    {
        return Location::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function event($slugOrId): Event
    {
        return Event::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    public function author($slugOrId): Author
    {
        return Author::where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();
    }

    private static function formatCategoryTree($records, $level = 0, $visited = null)
    {
        if ($visited === null) {
            $visited = [];
        }

        $list = [];

        foreach ($records as $record) {
            if (in_array($record->id, $visited)) {
                continue;
            }

            $visited[] = $record->id;

            $list[] = [
                'id'               => $record->id,
                'name'             => $record->name,
                'name_tree'        => $record->name_tree,
                'indentation_name' => $record->indentation_name,
            ];

            if ($record->children && $record->children->isNotEmpty()) {
                $list = array_merge(
                    $list,
                    self::formatCategoryTree($record->children, $level + 1, $visited)
                );
            }
        }

        return $list;
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

    private static function formatLocationTree($records, $level = 0, $visited = null)
    {
        if ($visited === null) {
            $visited = [];
        }

        $list = [];

        foreach ($records as $record) {
            if (in_array($record->id, $visited)) {
                continue;
            }

            $visited[] = $record->id;

            $list[] = [
                'id'               => $record->id,
                'name'             => $record->name,
                'name_tree'        => $record->name_tree,
                'indentation_name' => $record->indentation_name,
            ];

            if ($record->children && $record->children->isNotEmpty()) {
                $list = array_merge(
                    $list,
                    self::formatLocationTree($record->children, $level + 1, $visited)
                );
            }
        }

        return $list;
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
}

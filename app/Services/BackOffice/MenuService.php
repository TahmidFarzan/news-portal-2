<?php
namespace App\Services\BackOffice;

use App\Helpers\SystemHelper;
use App\Http\Requests\MenuItemRequest;
use App\Http\Requests\MenuRequest;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Tag;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MenuService
{
    public function new (): Menu
    {
        return new Menu();
    }

    public function find(string $slug): Menu
    {
        return Menu::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Menu $menu): Menu
    {
        $menu->load([
            'language',
            'menuType',

            'createdBy',

            'menuItems'    => fn($query)    => $query->latest()->limit(5),
            'menuItems.language',
            'menuItems.parent',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $menu;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Menu::query()->with("language");

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
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(MenuRequest $request, Menu $menu): array
    {
        $isNew       = empty($menu->id);
        $statusEvent = $isNew ? "save" : "update";

        try {
            DB::transaction(function () use ($request, $menu, $isNew) {
                $menu->name          = $request->input('name');
                $menu->menu_type_id  = $request->input('menu_type_id');
                $menu->language_id   = $request->input('language_id');
                $menu->created_by_id = $isNew ? Auth::id() : $menu->created_by_id;

                $menu->save();
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.menu.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} menu.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.menu.save.failed'),
            ];
        }
    }

    public function delete(Menu $menu): array
    {
        try {
            DB::transaction(function () use ($menu) {
                $menu->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.menu.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Menu delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.menu.delete.failed'),
            ];
        }
    }

    public function menuItemNew(): MenuItem
    {
        return new MenuItem();
    }

    public function menuItemfind(Menu $menu, string $menuSlug): MenuItem
    {
        return $menu->menuItems()->where('slug', $menuSlug)->firstOrFail();
    }

    public function menuItemLoadRelations(MenuItem $menuItem): MenuItem
    {
        $menuItem->load([
            "parent",

            'model',
            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $menuItem;
    }

    public function menuItemSearch(Menu $menu, Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = MenuItem::query()->with("parent","language");

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('model_type')) {
            $modelType = $request->input('model_type');
            $query->where('model_type', 'like', "%{$modelType}%");
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $query->where('menu_id', $menu->id);

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function menuItemSave(MenuItemRequest $request, Menu $menu, MenuItem $menuItem): array
    {
        $isNew       = empty($menuItem->id);
        $statusEvent = $isNew ? "save" : "update";

        try {
            DB::transaction(function () use ($request, $menu, $menuItem, $isNew) {
                $modelRecord = null;

                switch (Str::studly($request->input('model_type'))) {
                    case SystemHelper::MENU_ITEM_MODEL_CATEGORY:
                        $modelRecord = Category::where("id", $request->input('model_id'))->first();
                        break;

                    case SystemHelper::MENU_ITEM_MODEL_TAG:
                        $modelRecord = Tag::where("id", $request->input('model_id'))->first();
                        break;

                    default:
                        $modelRecord = Category::where("id", $request->input('model_id'))->first();
                        break;
                }
                $menuItem->name        = $request->input('name');
                $menuItem->language_id = $request->input('language_id');
                $menuItem->model_type  = $request->boolean('is_custom_url') ? null : ($modelRecord?->getMorphClass() ?? null);
                $menuItem->model_id    = $request->boolean('is_custom_url') ? null : ($modelRecord?->id ?? null);
                $menuItem->parent_id   = $request->boolean('has_parent') ? $request->input('parent_id') : null;
                $menuItem->url         = $request->boolean('is_custom_url') ? $request->input('url ') : null;

                if ($isNew) {
                    $menuItem->position = $this->menuItemLastPosition($menu, $request->input('language_id'), $request->boolean('has_parent') ? $request->input('parent_id') : null) + 1;
                }

                $menuItem->menu_id       = $menu->id;
                $menuItem->created_by_id = $isNew ? Auth::id() : $menu->created_by_id;

                $menuItem->save();

                if ($request->input('position')) {
                    $this->menuItemPositionSyncUpdate($menu, $menuItem, $request->input('position'));
                    $menuItem->update([
                        'position' => $request->input('position'),
                    ]);
                }
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.menu_item.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} menu item.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.menu_item.save.failed'),
            ];
        }
    }

    public function menuItemDelete(Menu $menu, MenuItem $menuItem): array
    {
        try {
            DB::transaction(function () use ($menu, $menuItem) {
                $languageId = $menuItem->language_id;
                $parentId   = $menuItem->parent_id ?? null;

                $menuItem->delete();

                $this->menuItemPositionSyncAfterDelete($menu, $languageId, $parentId);
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.menu_item.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Menu item delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.menu_item.delete.failed'),
            ];
        }
    }

    private function menuItemPositionSyncUpdate(Menu $menu, MenuItem $givenMenuItem, ?int $updatePosition): void
    {
        if (
            $updatePosition === null ||
            $givenMenuItem->position === null ||
            $updatePosition === $givenMenuItem->position
        ) {
            return;
        }

        $baseQuery = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('language_id', $givenMenuItem->language_id)
            ->whereKeyNot($givenMenuItem->id)
            ->whereNotNull('position');

        if ($givenMenuItem->parent_id) {
            $baseQuery->where('parent_id', $baseQuery->parent_id);
        }

        $currentPosition = $givenMenuItem->position;

        if ($updatePosition < $currentPosition) {
            (clone $baseQuery)
                ->whereBetween('position', [$updatePosition, $currentPosition - 1])
                ->increment('position');
        } else {
            (clone $baseQuery)
                ->whereBetween('position', [$currentPosition + 1, $updatePosition])
                ->decrement('position');
        }
    }

    private function menuItemLastPosition(Menu $menu, int $languageId, ?int $parentId)
    {
        $position = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('language_id', $languageId);

        if ($parentId) {
            $position->where('parent_id', $parentId);
        }
        return $position->max("position");
    }

    private function menuItemPositionSyncAfterDelete(Menu $menu, int $languageId, ?int $parentId): void
    {
        $position = 1;

        $query = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('language_id', $languageId);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        }

        $query->select(['id'])
            ->orderByRaw('position IS NULL')
            ->orderBy('position')
            ->orderBy('id')
            ->chunk(500, function ($menuItems) use (&$position) {
                foreach ($menuItems as $menuItem) {
                    MenuItem::query()
                        ->whereKey($menuItem->id)
                        ->update([
                            'position' => $position,
                        ]);

                    $position++;
                }
            });
    }
}

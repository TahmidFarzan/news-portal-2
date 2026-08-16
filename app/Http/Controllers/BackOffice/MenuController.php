<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Http\Requests\MenuItemRequest;
use App\Services\BackOffice\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MenuController extends Controller
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function index(Request $request): InertiaResponse
    {
        $menu = $this->menuService->new();
        Gate::authorize('viewAny', $menu);

        $menus = $this->menuService->search($request);

        return Inertia::render('back-office/menus/Index', [
            'menus' => $menus,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $menu = $this->menuService->find($slug);

        Gate::authorize('view', $menu);

        return Inertia::render('back-office/menus/Details', [
            'menu' => $menu,
        ]);
    }

    public function create(): InertiaResponse
    {
        $menu = $this->menuService->new();
        Gate::authorize('create', $menu);

        return Inertia::render('back-office/menus/Create', [
            'menu' => $menu,
        ]);
    }

    public function edit(string $slug): InertiaResponse
    {
        $menu = $this->menuService->find($slug);

        Gate::authorize('update', $menu);

        return Inertia::render('back-office/menus/Create', [
            'menu' => $menu,
        ]);
    }

    public function save(MenuRequest $request): RedirectResponse
    {
        $menu = $this->menuService->new();
        Gate::authorize('create', $menu);

        $result = $this->menuService->save($request, $menu);

        return to_route('back-office.menus.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(MenuRequest $request, string $slug): RedirectResponse
    {
        $menu = $this->menuService->find($slug);

        Gate::authorize('update', $menu);

        $result = $this->menuService->save($request, $menu);

        return to_route('back-office.menus.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug): RedirectResponse
    {
        $menu = $this->menuService->find($slug);

        Gate::authorize('delete', $menu);

        $result = $this->menuService->delete($menu);

        return to_route('back-office.menus.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function menuItemIndex(Request $request, string $slug): InertiaResponse
    {
        $menu     = $this->menuService->find($slug);
        $menuItem = $this->menuService->menuItemNew();
        Gate::authorize('viewAny', $menuItem);

        $menuItems = $this->menuService->menuItemSearch($menu, $request);

        return Inertia::render('back-office/menus/menu-items/Index', [
            'menu' => $menu,
            'menuItems' => $menuItems,
        ]);
    }

    public function menuItemCreate(string $slug): InertiaResponse
    {
        $menu     = $this->menuService->find($slug);
        $menuItem = $this->menuService->menuItemNew();
        Gate::authorize('create', $menuItem);

        return Inertia::render('back-office/menus/menu-items/Create', [
            'menu' => $menu,
        ]);
    }

    public function menuItemEdit(string $slug, string $menuItemSlug): InertiaResponse
    {
        $menu = $this->menuService->find($slug);

        $menuItem = $this->menuService->menuItemfind($menu, $menuItemSlug);
        Gate::authorize('update', $menuItem);

        return Inertia::render('back-office/menus/menu-items/Create', [
            'menu' => $menu,
            'menuItem' => $menuItem,
        ]);
    }

    public function menuItemDetails(string $slug, string $menuItemSlug): InertiaResponse
    {
        $menu = $this->menuService->find($slug);

        $menuItem = $this->menuService->menuItemfind($menu, $menuItemSlug);

        Gate::authorize('view', $menuItem);

        return Inertia::render('back-office/menus/menu-items/Details', [
            'menu' => $menu,
            'menuItem' => $menuItem,
        ]);
    }

    public function menuItemSave(MenuItemRequest $request, string $slug): RedirectResponse
    {
        $menu = $this->menuService->find($slug);
        $menuItem = $this->menuService->menuItemNew();

        Gate::authorize('save', $menuItem);

        $result = $this->menuService->menuItemSave($request, $menu, $menuItem);

        return to_route('back-office.menus.menu-items.index',["slug" => $slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function menuItemUpdate(MenuItemRequest $request, string $slug, string $menuItemSlug): RedirectResponse
    {
        $menu = $this->menuService->find($slug);
        $menuItem = $this->menuService->menuItemfind($menu, $menuItemSlug);

        Gate::authorize('update', $menuItem);

        $result = $this->menuService->menuItemSave($request, $menu, $menuItem);

        return to_route('back-office.menus.menu-items.index',["slug" => $slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function menuItemDelete(string $slug, string $menuItemSlug): RedirectResponse
    {
        $menu = $this->menuService->find($slug);
        $menuItem = $this->menuService->menuItemfind($menu, $menuItemSlug);

        Gate::authorize('delete', $menu);

        $result = $this->menuService->menuItemDelete($menu, $menuItem);

        return to_route('back-office.menus.menu-items.index',["slug" => $slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}

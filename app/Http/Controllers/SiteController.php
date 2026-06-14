<?php
namespace App\Http\Controllers;

use App\Services\SiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    protected SiteService $siteService;

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    public function language(): JsonResponse
    {
        return response()->json(
            $this->siteService->language()
        );
    }

    public function menuHeaderMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->menuHeaderMenuMenuItems($request)
        );
    }

    public function menuOffCanvasMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->menuOffCanvasMenuMenuItems($request)
        );
    }

    public function menuTopbarMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->menuTopbarMenuMenuItems($request)
        );
    }

    public function menuFooterMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->menuFooterMenuMenuItems($request)
        );
    }

    public function menuItemSubMenuItems(Request $request, string $slug): JsonResponse
    {
        $menuItem = $this->siteService->menuItem($slug);
        $menuItem = $this->siteService->menuItemRelationLoad($menuItem);
        return response()->json(
            $this->siteService->menuItemSubMenuItems($request, $menuItem)
        );
    }

    public function settings(): JsonResponse
    {
        return response()->json(
            $this->siteService->settings()
        );
    }

    public function breakingNews(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->breakingNews($request)
        );
    }

    public function languages(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->languages($request)
        );
    }

    public function languageChange(int | string $slugOrId)
    {
        $result = $this->siteService->languageChange($slugOrId);

        return response()->json($result);
    }
}

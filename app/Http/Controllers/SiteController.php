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

    public function themeMenuHeaderMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->themeMenuHeaderMenuMenuItems($request)
        );
    }

    public function themeMenuOffCanvasMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->themeMenuOffCanvasMenuMenuItems($request)
        );
    }

    public function themeMenuTopbarMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->themeMenuTopbarMenuMenuItems($request)
        );
    }

    public function themeMenuFooterMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->themeMenuFooterMenuMenuItems($request)
        );
    }

    public function themeMenuItemSubMenuItems(Request $request, string $slug): JsonResponse
    {
        $menuItem = $this->siteService->menuItem($slug);
        $menuItem = $this->siteService->menuItemRelationLoad($menuItem);
        return response()->json(
            $this->siteService->themeMenuItemSubMenuItems($request, $menuItem)
        );
    }
}

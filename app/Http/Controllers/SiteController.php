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

    public function themeHeaderMenuMenuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->themeHeaderMenuMenuItems($request)
        );
    }

    public function themeMenuItemSubMenuItems(Request $request, string $slug): JsonResponse
    {
        $menuItem = $this->siteService->menuItem($slug);
        return response()->json(
            $this->siteService->themeMenuItemSubMenuItems($request, $menuItem)
        );
    }
}

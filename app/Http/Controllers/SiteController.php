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

    public function language($slug): JsonResponse
    {
        return response()->json(
            $this->siteService->language($slug)
        );
    }

    public function defaultLanguage(): JsonResponse
    {
        return response()->json(
            $this->siteService->defaultLanguage()
        );
    }

    public function menuHeaderMenuMenuItems(): JsonResponse
    {
        return response()->json(
            $this->siteService->menuHeaderMenuMenuItems()
        );
    }

    public function menuOffCanvasMenuMenuItems(): JsonResponse
    {
        return response()->json(
            $this->siteService->menuOffCanvasMenuMenuItems()
        );
    }

    public function menuTopbarMenuMenuItems(): JsonResponse
    {
        return response()->json(
            $this->siteService->menuTopbarMenuMenuItems()
        );
    }

    public function menuFooterMenuMenuItems(): JsonResponse
    {
        return response()->json(
            $this->siteService->menuFooterMenuMenuItems()
        );
    }

    public function menuItemSubMenuItems(string $slug): JsonResponse
    {
        $menuItem = $this->siteService->menuItem($slug);

        return response()->json(
            $this->siteService->menuItemSubMenuItems($menuItem)
        );
    }

    public function themes(): JsonResponse
    {
        return response()->json(
            $this->siteService->themes()
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

    public function languageChange(int|string $slugOrId): JsonResponse
    {
        $result = $this->siteService->languageChange($slugOrId);

        return response()->json($result);
    }

    public function getGoogleAdsence(Request $request): JsonResponse
    {
        $GoogleAdsences = $this->siteService->getGoogleAdsence($request);

        return response()->json($GoogleAdsences);
    }
}

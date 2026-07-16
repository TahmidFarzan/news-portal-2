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

    public function language(string | null $code=null): JsonResponse
    {
        return response()->json(
            $this->siteService->language($code)
        );
    }

    public function defaultLanguage(): JsonResponse
    {
        return response()->json(
            $this->siteService->defaultLanguage()
        );
    }

    public function menuHeaderMenuMenuItems(string | null $languageCode=null): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuHeaderMenuMenuItems($language)
        );
    }

    public function menuOffCanvasMenuMenuItems(string | null $languageCode=null): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuOffCanvasMenuMenuItems($language)
        );
    }

    public function menuTopbarMenuMenuItems(string | null $languageCode=null): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuTopbarMenuMenuItems($language)
        );
    }

    public function menuFooterMenuMenuItems(string | null $languageCode=null): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuFooterMenuMenuItems($language)
        );
    }

    public function menuItemSubMenuItems(string $slug, string | null $languageCode=null): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        $menuItem = $this->siteService->menuItem($slug, $language);

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

    public function breakingNews(Request $request, string | null $languageCode=null): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->breakingNews($request, $language)
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

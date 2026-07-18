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

    public function language(string $code): JsonResponse
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

    public function menuHeaderMenuMenuItems(): JsonResponse
    {
        $language = $this->siteService->defaultLanguage();
        return response()->json(
            $this->siteService->menuHeaderMenuMenuItems($language)
        );
    }

    public function menuOffCanvasMenuMenuItems(): JsonResponse
    {
        $language = $this->siteService->defaultLanguage();
        return response()->json(
            $this->siteService->menuOffCanvasMenuMenuItems($language)
        );
    }

    public function menuTopbarMenuMenuItems(): JsonResponse
    {
        $language = $this->siteService->defaultLanguage();
        return response()->json(
            $this->siteService->menuTopbarMenuMenuItems($language)
        );
    }

    public function menuFooterMenuMenuItems(): JsonResponse
    {
        $language = $this->siteService->defaultLanguage();
        return response()->json(
            $this->siteService->menuFooterMenuMenuItems($language)
        );
    }

    public function menuItemSubMenuItems(string $slug): JsonResponse
    {
        $language = $this->siteService->defaultLanguage();
        $menuItem = $this->siteService->menuItem($language, $slug);

        return response()->json(
            $this->siteService->menuItemSubMenuItems($menuItem)
        );
    }

    public function breakingNews(Request $request): JsonResponse
    {
        $language = $this->siteService->defaultLanguage();
        return response()->json(
            $this->siteService->breakingNews($request, $language)
        );
    }

    public function localizedMenuHeaderMenuMenuItems(string $languageCode): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuHeaderMenuMenuItems($language)
        );
    }

    public function localizedMenuOffCanvasMenuMenuItems(string $languageCode): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuOffCanvasMenuMenuItems($language)
        );
    }

    public function localizedMenuTopbarMenuMenuItems(string $languageCode): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuTopbarMenuMenuItems($language)
        );
    }

    public function localizedMenuFooterMenuMenuItems(string $languageCode): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->menuFooterMenuMenuItems($language)
        );
    }

    public function localizedMenuItemSubMenuItems(string $languageCode, string $slug): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        $menuItem = $this->siteService->menuItem($language, $slug);

        return response()->json(
            $this->siteService->menuItemSubMenuItems($menuItem)
        );
    }

    public function localizedBreakingNews(Request $request, string $languageCode): JsonResponse
    {
        $language = $this->siteService->language($languageCode);
        return response()->json(
            $this->siteService->breakingNews($request, $language)
        );
    }

    public function themes(): JsonResponse
    {
        return response()->json(
            $this->siteService->themes()
        );
    }

    public function languages(Request $request): JsonResponse
    {
        return response()->json(
            $this->siteService->languages($request)
        );
    }

    public function getGoogleAdsence(Request $request): JsonResponse
    {
        $GoogleAdsences = $this->siteService->getGoogleAdsence($request);

        return response()->json($GoogleAdsences);
    }
}

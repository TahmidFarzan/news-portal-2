<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MediaHelper
{
    public const DEFAULT_CONVERSION = 'webp';

    public const ROLE_DEFAULT           = 'default';
    public const ROLE_PROFILE_IMAGE     = 'profile_image';
    public const ROLE_APP_LOGO_IMAGE    = 'app_logo_image';
    public const ROLE_APP_FAVICON_IMAGE = 'app_favicon_image';

    public const ROLE_NEWS_FEATURE_IMAGE        = 'news_feature_image';
    public const ROLE_NEWS_FEATURE_IMAGE_MOBILE = 'news_feature_image_mobile';
    public const ROLE_NEWS_CONTENT_IMAGE        = 'news_content_image';

    public const ROLE_NEWS_GALLERY_IMAGE = 'news_gallery_image';

    public const ROLE_EVENT_DESKTOP_BANNER_IMAGE = 'event_desktop_banner_image';
    public const ROLE_EVENT_MOBILE_BANNER_IMAGE  = 'event_mobile_banner_image';

    public static function mediaRoles()
    {
        return collect([
            (object) ['id' => self::ROLE_DEFAULT, 'name' => 'Default'],

            (object) ['id' => self::ROLE_NEWS_FEATURE_IMAGE, 'name' => 'News feature image'],
            (object) ['id' => self::ROLE_NEWS_FEATURE_IMAGE_MOBILE, 'name' => 'News feature image (Mobile)'],
            (object) ['id' => self::ROLE_NEWS_CONTENT_IMAGE, 'name' => 'News content image'],

            (object) ['id' => self::ROLE_PROFILE_IMAGE, 'name' => 'Profile Image'],

            (object) ['id' => self::ROLE_APP_LOGO_IMAGE, 'name' => 'App Logo Image'],
            (object) ['id' => self::ROLE_APP_FAVICON_IMAGE, 'name' => 'App Favicon Image'],

            (object) ['id' => self::ROLE_EVENT_DESKTOP_BANNER_IMAGE, 'name' => 'Event desktop banner image'],
            (object) ['id' => self::ROLE_EVENT_MOBILE_BANNER_IMAGE, 'name' => 'Event mobile banner image'],

        ]);
    }

    public static function generateMediaName(string $mediaName, string $mediaExtension, string | int $maxLength)
    {
        $mediaName = $mediaName ?? "File";
        $mediaName = Str::of($mediaName)->limit($maxLength)->__toString();

        $mediaName    = preg_replace('/[^a-z0-9]+/', '', strtolower($mediaName));
        $randomString = Str::random(5);

        $timestamp              = date('Ymdhis');
        $userId                 = Auth::check() ? '-u' . Auth::id() : '';
        $mediaNameWithExtension = "{$mediaName}-{$randomString}-{$timestamp}{$userId}.{$mediaExtension}";

        return $mediaNameWithExtension;
    }

    public static function parseAndRebuildUrl($url)
    {
        $cleanUrl = $url;
        if (! ($url == null) && (strpos($url, '?') !== false)) {
            $urlComponents = parse_url($url);
            $cleanUrl      = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'];
        }
        return $cleanUrl;
    }

    public static function defaultAppImage($resulation = "1:1", $mediaName = null)
    {
        $mediaUrl     = self::demoImageUrlByResulation($resulation, "App") ?? null;
        $replacements = ['&' => 'and', "'" => ''];

        $formatedMediaName = Str::replace(array_keys($replacements), array_values($replacements), $mediaName);
        $mediaFileName     = Str::lower(Str::slug($formatedMediaName));

        $mediaPath = "uploads/icons/app/{$mediaFileName}.png";

        $mediaPublicPath = public_path($mediaPath);

        if (file_exists($mediaPublicPath)) {
            $mediaUrl = asset($mediaPath);
        }

        return $mediaUrl;
    }

    public static function defaultAuthImage($resulation = "1:1", $mediaName = "user")
    {
        $mediaUrl     = self::demoImageUrlByResulation($resulation, "App") ?? null;
        $replacements = ['&' => 'and', "'" => ''];

        $formatedMediaName = Str::replace(array_keys($replacements), array_values($replacements), $mediaName);
        $mediaFileName     = Str::lower(Str::slug($formatedMediaName));
        $mediaPath         = "uploads/icons/auth/{$mediaFileName}.png";

        $mediaPublicPath = public_path($mediaPath);

        if (file_exists($mediaPublicPath)) {
            $mediaUrl = asset($mediaPath);
        }

        return $mediaUrl;
    }

    public static function demoImageUrlByResulation($resulation = "1:1", $text = null)
    {
        return self::imageUrlGenerateFormOnlineByResulation($resulation, $text);
    }

    public static function demoImageUrl($imageWidth, $imageHeight, $text = null)
    {
        return self::imageUrlGenerateFormOnline($imageWidth, $imageHeight, $text);
    }

    private static function imageUrlGenerateFormOnlineByResulation($resulation, $text = null)
    {
        $imageSize = self::imageWidthHeightByRatio($resulation);

        $imageWidth  = $imageSize["width"];
        $imageHeight = $imageSize["height"];

        return self::imageUrlGenerateFormOnline($imageWidth, $imageHeight, $text);
    }

    private static function imageUrlGenerateFormOnline($imageWidth, $imageHeight, $text = null, $imageBgColor = "ededed", $imageBgTextColor = "000000")
    {
        $imageBgColor     = $imageBgColor ?? "ededed";
        $imageBgTextColor = $imageBgTextColor ?? "000000";

        $url = "https://dummyimage.com/{$imageWidth}x{$imageHeight}/{$imageBgColor}/{$imageBgTextColor}";

        if ($text && ! ($text == null)) {
            $url = "$url&text={$text}";
        }
        return $url;
    }

    private static function imageWidthHeightByRatio($resulation = "1:1")
    {
        $width  = 512;
        $height = 512;

        switch ($resulation) {
            case "16:9":
                $width  = 1280;
                $height = 720;
                break;

            case "4:3":
                $width  = 400;
                $height = 300;
                break;

            case "3:4":
                $width  = 300;
                $height = 400;
                break;

            case "2:3":
                $width  = 400;
                $height = 600;
                break;

            case "3:2":
                $width  = 600;
                $height = 400;
                break;

            case "1:1":
                $width  = 512;
                $height = 512;
                break;

            case "8:1":
                $width  = 728;
                $height = 90;
                break;

            case "1:1.2":
                $width  = 300;
                $height = 250;
                break;

            default:
                $width  = 400;
                $height = 255;
                break;
        }

        return [
            "width"  => $width,
            "height" => $height,
        ];
    }
}

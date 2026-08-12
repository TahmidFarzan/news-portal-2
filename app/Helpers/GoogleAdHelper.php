<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class GoogleAdHelper
{
    public const POPUP_LABEL = 'Pop Up';
    public const POPUP_AD_TYPE_SEPARATOR = ' - ';

    public const DEFAULT_AD_SIZES = [
        [300, 250],
    ];

    public const POPUP_AD_PAGE_HOME = 'Home Page';
    public const POPUP_AD_PAGE_LATEST = 'Latest Page';
    public const POPUP_AD_PAGE_SEARCH = 'Search Page';
    public const POPUP_AD_PAGE_VIDEO = 'Video Page';
    public const POPUP_AD_PAGE_IMAGE_GALLERY = 'Image Gallery Page';
    public const POPUP_AD_PAGE_CATEGORY = 'Category Page';
    public const POPUP_AD_PAGE_TAG = 'Tag Page';
    public const POPUP_AD_PAGE_EVENT = 'Event Page';
    public const POPUP_AD_PAGE_LOCATION = 'Location Page';
    public const POPUP_AD_PAGE_NEWS_DETAILS = 'News Details Page';
    public const POPUP_AD_PAGE_CONTACT = 'Contact Page';
    public const POPUP_AD_PAGE_ABOUT = 'About Page';
    public const POPUP_AD_PAGE_OTHER = 'Other Page';

    public const TYPE_SECTION = 'Section';
    public const TYPE_SIDEBAR = 'Sidebar';
    public const TYPE_POPUP_HOME_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_HOME;
    public const TYPE_POPUP_LATEST_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_LATEST;
    public const TYPE_POPUP_SEARCH_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_SEARCH;
    public const TYPE_POPUP_VIDEO_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_VIDEO;
    public const TYPE_POPUP_IMAGE_GALLERY_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_IMAGE_GALLERY;
    public const TYPE_POPUP_CATEGORY_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_CATEGORY;
    public const TYPE_POPUP_TAG_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_TAG;
    public const TYPE_POPUP_EVENT_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_EVENT;
    public const TYPE_POPUP_LOCATION_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_LOCATION;
    public const TYPE_POPUP_NEWS_DETAILS_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_NEWS_DETAILS;
    public const TYPE_POPUP_CONTACT_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_CONTACT;
    public const TYPE_POPUP_ABOUT_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_ABOUT;
    public const TYPE_POPUP_OTHER_PAGE = self::POPUP_LABEL . self::POPUP_AD_TYPE_SEPARATOR . self::POPUP_AD_PAGE_OTHER;

    public const POSITION_TOP = 'Top';
    public const POSITION_BETWEEN = 'Between';
    public const POSITION_BOTTOM = 'Bottom';

    public static function types(): Collection
    {
        return SystemHelper::toOptions([
            self::TYPE_SECTION,
            self::TYPE_SIDEBAR,

            self::TYPE_POPUP_HOME_PAGE,
            self::TYPE_POPUP_LATEST_PAGE,
            self::TYPE_POPUP_SEARCH_PAGE,
            self::TYPE_POPUP_VIDEO_PAGE,
            self::TYPE_POPUP_IMAGE_GALLERY_PAGE,
            self::TYPE_POPUP_CATEGORY_PAGE,
            self::TYPE_POPUP_TAG_PAGE,
            self::TYPE_POPUP_EVENT_PAGE,
            self::TYPE_POPUP_LOCATION_PAGE,
            self::TYPE_POPUP_NEWS_DETAILS_PAGE,
            self::TYPE_POPUP_CONTACT_PAGE,
            self::TYPE_POPUP_ABOUT_PAGE,
            self::TYPE_POPUP_OTHER_PAGE,
        ]);
    }

    public static function positions(): Collection
    {
        return SystemHelper::toOptions([
            self::POSITION_TOP,
            self::POSITION_BETWEEN,
            self::POSITION_BOTTOM,
        ]);
    }
}

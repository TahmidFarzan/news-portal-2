<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class GoogleAdHelper
{
    public const DEFAULT_AD_SIZES = [
        [300, 250],
    ];

    public const PAGE_HOME = 'Home';
    public const PAGE_LATEST = 'Latest';
    public const PAGE_SEARCH = 'Search';
    public const PAGE_VIDEO = 'Video';
    public const PAGE_IMAGE_GALLERY = 'Image Gallery';
    public const PAGE_CATEGORY = 'Category';
    public const PAGE_TAG = 'Tag';
    public const PAGE_EVENT = 'Event';
    public const PAGE_LOCATION = 'Location';
    public const PAGE_CONTRIBUTOR = 'Contributor';
    public const PAGE_NEWS_DETAILS = 'News Details';
    public const PAGE_CONTACT = 'Contact';
    public const PAGE_ABOUT = 'About';
    public const PAGE_OTHER = 'Other';
    public const PAGE_QUIZ_DETAILS = 'Quiz Details';

    public const TYPE_SECTION = 'Section';
    public const TYPE_SIDEBAR = 'Sidebar';
    public const TYPE_POPUP = 'Pop Up';

    public const PLACEMENT_1 = '1';
    public const PLACEMENT_2 = '2';
    public const PLACEMENT_3 = '3';
    public const PLACEMENT_4 = '4';
    public const PLACEMENT_5 = '5';
    public const PLACEMENT_6 = '6';

    public static function pages(): Collection
    {
        return SystemHelper::toOptions([
            self::PAGE_HOME,
            self::PAGE_LATEST,
            self::PAGE_SEARCH,
            self::PAGE_VIDEO,
            self::PAGE_IMAGE_GALLERY,
            self::PAGE_CATEGORY,
            self::PAGE_CONTRIBUTOR,
            self::PAGE_TAG,
            self::PAGE_EVENT,
            self::PAGE_LOCATION,
            self::PAGE_NEWS_DETAILS,
            self::PAGE_CONTACT,
            self::PAGE_ABOUT,
            self::PAGE_OTHER,
            self::PAGE_QUIZ_DETAILS
        ]);
    }

    public static function types(): Collection
    {
        return SystemHelper::toOptions([
            self::TYPE_SECTION,
            self::TYPE_SIDEBAR,
            self::TYPE_POPUP,
        ]);
    }

    public static function placements(?string $page = null, ?string $type = null): Collection
    {
        $placements = [
            self::PLACEMENT_1,
            self::PLACEMENT_2,
            self::PLACEMENT_3,
            self::PLACEMENT_4,
            self::PLACEMENT_5,
            self::PLACEMENT_6,
        ];

        if ($page === null && $type === null) {
            return SystemHelper::toOptions($placements);
        }

        if ($type === null) {
            return SystemHelper::toOptions($placements);
        }

        if ($type === self::TYPE_POPUP) {
            return collect();
        }

        if ($type === self::TYPE_SIDEBAR) {
            return SystemHelper::toOptions([
                self::PLACEMENT_1,
                self::PLACEMENT_2,
            ]);
        }

        if ($type === self::TYPE_SECTION) {
            if ($page === self::PAGE_HOME) {
                return SystemHelper::toOptions($placements);
            }

            return SystemHelper::toOptions([
                self::PLACEMENT_1,
                self::PLACEMENT_2,
                self::PLACEMENT_3,
            ]);
        }

        return collect();
    }
}

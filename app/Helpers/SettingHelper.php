<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class SettingHelper
{
    public const GROUP_MENU  = "Menu";
    public const GROUP_SOCIAL_LINK  = "Social Link";
    public const GROUP_APP  = "App";

    public const TYPE_TEXT = 'Text';
    public const TYPE_STRING = 'String';
    public const TYPE_BOOLEAN = 'Boolean';
    public const TYPE_INTEGER = 'Integer';
    public const TYPE_FLOAT = 'Float';
    public const TYPE_DECIMAL = 'Decimal';
    public const TYPE_JSON = 'Json';
    public const TYPE_ARRAY = 'Array';
    public const TYPE_URL = 'Url';
    public const TYPE_IMAGE = 'Image';
    public const TYPE_COLOR = 'Color';

    public const FIELD_SHOW_FOOTER_MENU  = "Show Footer Menu";
    public const FIELD_SHOW_TOPBAR_MENU  = "Show Topbar Menu";

    public const FIELD_FB_SOCIAL_LINK  = "Fb Social Link";
    public const FIELD_YOUTUBE_SOCIAL_LINK  = "Youtube Social Link";
    public const FIELD_GOOGLE_NEWS_SOCIAL_LINK  = "Google News Link";

    public const FIELD_SHOW_LOGO_ON_HEADER_MENU  = "Show Logo On Header Menu";
    public const FIELD_SHOW_NAME_ON_HEADER_MENU  = "Show Name On Header Menu";

    public const FIELD_SHOW_BREAKING_NEWS  = "Show Breaking News";

}

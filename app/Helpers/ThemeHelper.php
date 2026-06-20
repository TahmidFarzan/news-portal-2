<?php
namespace App\Helpers;

use Illuminate\Support\Collection;

class ThemeHelper
{
    public const GROUP_MENU  = "Menu";
    public const GROUP_SOCIAL_LINK  = "Social Link";
    public const GROUP_APP  = "App";

    public const VALUE_TYPE_TEXT = 'Text';
    public const VALUE_TYPE_STRING = 'String';
    public const VALUE_TYPE_BOOLEAN = 'Boolean';
    public const VALUE_TYPE_INTEGER = 'Integer';
    public const VALUE_TYPE_FLOAT = 'Float';
    public const VALUE_TYPE_DECIMAL = 'Decimal';
    public const VALUE_TYPE_JSON = 'Json';
    public const VALUE_TYPE_ARRAY = 'Array';
    public const VALUE_TYPE_URL = 'Url';
    public const VALUE_TYPE_IMAGE = 'Image';
    public const VALUE_TYPE_COLOR = 'Color';

    public const OPTION_SHOW_FOOTER_MENU  = "Show Footer Menu";
    public const OPTION_SHOW_TOPBAR_MENU  = "Show Topbar Menu";

    public const OPTION_FB_SOCIAL_LINK  = "Fb Social Link";
    public const OPTION_YOUTUBE_SOCIAL_LINK  = "Youtube Social Link";
    public const OPTION_GOOGLE_NEWS_SOCIAL_LINK  = "Google News Link";

    public const OPTION_SHOW_LOGO_ON_HEADER_MENU  = "Show Logo On Header Menu";
    public const OPTION_SHOW_NAME_ON_HEADER_MENU  = "Show Name On Header Menu";

    public const OPTION_SHOW_BREAKING_NEWS  = "Show Breaking News";

}

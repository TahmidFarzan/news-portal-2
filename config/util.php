<?php

return [
    'news' => [
        'default_language' => env('NEWS_DEFAULT_LANGUAGE', 'en_us'),
    ],
    'tinymce' => [
        'license_key' => env('TINY_MCE_TEXT_EDITOR_LICENSE_KEY', 'gpl'),
    ],


    'google-ad' => [
        'test_client_id' => env('GOOGLE_AD_TEST_CLIENT_ID', "ca-pub-3940256099942544"),
        'test_ad_slot'   => env('GOOGLE_AD_TEST_AD_SLOT', "6300978111"),
    ],
];

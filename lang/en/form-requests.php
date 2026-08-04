<?php

return [
    "api"                  => [
        'timeout' => 'Api responce timeout.',
    ],

    'auth'                 => [
        'login'           => [
            'email'    => [
                'required' => 'Please enter your email address.',
                'email'    => 'Enter a valid email format.',
                'exists'   => 'Email does not found.',
            ],
            'password' => [
                'required' => 'Your password is required.',
            ],
        ],

        'register'        => [
            "is_not_allow" => "Registration is not allow",
            'name'         => [
                'required' => 'Please enter your name.',
            ],
            'email'        => [
                'required' => 'Please provide a valid email address.',
                'email'    => 'The email must be a valid email format.',
                'unique'   => 'This email is already registered.',
            ],
            'password'     => [
                'required'  => 'Please choose a password.',
                'min'       => 'Password must be at least 8 characters.',
                'confirmed' => 'Password confirmation does not match.',
            ],
        ],

        'forgot_password' => [
            'email' => [
                'required' => 'Please enter your email address.',
                'email'    => 'Enter a valid email address.',
                'exists'   => 'We can’t find a user with that email address.',
            ],
        ],

        'reset_password'  => [
            'token'    => [
                'required' => 'The reset token is missing or invalid.',
            ],
            'email'    => [
                'required' => 'Please enter your email.',
                'email'    => 'Enter a valid email address.',
                'exists'   => 'No account found with this email.',
            ],
            'password' => [
                'required'  => 'Please enter a new password.',
                'min'       => 'Password must be at least 8 characters.',
                'confirmed' => 'Password confirmation does not match.',
            ],
        ],

        'user_account'    => [
            'name'                  => [
                'required' => 'Name is required.',
                'string'   => 'Name must be string.',
                'max'      => 'Name max chars is 200.',
            ],
            'email'                 => [
                'required' => 'Email is required.',
                'string'   => 'Email must be string.',
                'max'      => 'Email max chars is 200.',
                'unique'   => 'Email must be unique.',
            ],
            'change_password'       => [
                'required' => 'Change password is required.',
            ],
            'password_confirmation' => [
                'required' => 'Password confirmation is required.',
            ],

            'current_password'      => [
                'required' => 'Please enter a current password.',
            ],

            'password'              => [
                'required'  => 'Please enter a password.',
                'min'       => 'Password must be at least 8 characters.',
                'confirmed' => 'Password confirmation does not match.',
            ],
        ],

        'user_profile'    => [
            'name'           => [
                'required' => 'Name is required.',
                'max'      => 'Name max char is 255.',
            ],
            'birth_date'     => [
                'date' => 'Birth date must be a date.',
            ],
            'gender'         => [
                'required' => 'Gender is required.',
            ],
            'religion'       => [
                'required' => 'Religion is required.',
            ],
            'marital_status' => [
                'required' => 'Marital status is required.',
            ],
            'mobile'         => [
                'max'   => 'Mobile max char is 20.',
                'regex' => 'Mobile must be number.',
            ],
            'profile_image'  => [
                'image'      => 'Upload feature image must be image.',
                'mimes'      => 'Upload feature image is mimes must be one out of [jpg,jpeg,png,webp].',
                'dimensions' => 'Upload feature image must be dimensions ratio is 1:1 and  size 512x512px.',
            ],
        ],
    ],

    'media_quick'          => [
        'alt'     => [
            'string' => 'Alt must be string.',
        ],

        'caption' => [
            'string' => 'Caption must be string.',
        ],

        'media'   => [
            'required' => 'Media is required.',
        ],
    ],

    'user'                 => [
        'name'                  => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],
        'email'                 => [
            'required' => 'Email is required.',
            'string'   => 'Email must be string.',
            'max'      => 'Email max chars is 200.',
            'unique'   => 'Email must be unique.',
        ],
        'birth_date'            => [
            'date' => 'Birth date must be a date.',
        ],
        'gender'                => [
            'required' => 'Gender is required.',
        ],
        'religion'              => [
            'required' => 'Religion is required.',
        ],
        'marital_status'        => [
            'required' => 'Marital status is required.',
        ],
        'mobile'                => [
            'max'   => 'Mobile max char is 20.',
            'regex' => 'Mobile must be number.',
        ],
        'change_password'       => [
            'required' => 'Change password is required.',
            'boolean'  => 'Change password must be true or false.',
        ],

        'password_confirmation' => [
            'required_if' => 'Password confirmation is required.',
            'boolean'     => 'Password confirmation must be true or false.',
        ],

        'password'              => [
            'required'  => 'Please enter a password.',
            'min'       => 'Password must be at least 8 characters.',
            'confirmed' => 'Password confirmation does not match.',
        ],

        'user_permission_ids'   => [
            'required'               => 'Please select user permission.',
            "not_found"              => 'Selected user permission does not exit.',
            "do_not_have_permission" => 'Please select another user permission. You can not create user using this user permission.',
        ],

        'profile_image'         => [
            'image'      => 'Upload feature image must be image.',
            'mimes'      => 'Upload feature image is mimes must be one out of [jpg,jpeg,png,webp].',
            'dimensions' => 'Upload feature image must be dimensions ratio is 1:1 and  size 512x512px.',
        ],
    ],

    'language'             => [
        'name'   => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],
        'code'   => [
            'required' => 'Code is required.',
            'string'   => 'Code must be string.',
            'max'      => 'Code max chars is 200.',
            'unique'   => 'Code must be unique.',
        ],

        'locale' => [
            'required' => 'Locale is required.',
            'string'   => 'Locale must be string.',
            'max'      => 'Locale max chars is 200.',
            'unique'   => 'Locale must be unique.',
        ],
    ],

    'category'             => [
        'name'        => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'parent_id'   => [
            'required'  => 'Parent is required.',
            "not_found" => 'Parent is not exit.',
        ],

        'language_id' => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],
    ],

    'tag'                  => [
        'name'        => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'language_id' => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],
    ],

    'trend'                => [
        'tag_id'   => [
            'required'  => 'Tag is required.',
            "not_found" => 'Tag is not exit.',
            "unique"    => 'Tag must be unique.',
        ],

        'position' => [
            'numeric' => 'Tag must be numeric.',
        ],
    ],

    'location'             => [
        'name'             => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'parent_id'        => [
            'required'  => 'Parent is required.',
            "not_found" => 'Parent is not exit.',
        ],

        'language_id'      => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],

        'category_id'      => [
            'required' => 'Category is required.',
        ],

        'latitude'         => [
            'numeric' => 'The latitude must be a valid number.',
            'between' => 'The latitude must be between -90 and 90.',
        ],

        'longitude'        => [
            'numeric' => 'The longitude must be a valid number.',
            'between' => 'The longitude must be between -180 and 180.',
        ],

        'boundary_geojson' => [
            'valid' => 'The boundary GeoJSON must be a valid JSON object or array.',
        ],

        'boundary_north'   => [
            'numeric' => 'The north boundary must be a valid number.',
            'between' => 'The north boundary must be between -90 and 90.',
        ],

        'boundary_south'   => [
            'numeric' => 'The south boundary must be a valid number.',
            'between' => 'The south boundary must be between -90 and 90.',
        ],

        'boundary_east'    => [
            'numeric' => 'The east boundary must be a valid number.',
            'between' => 'The east boundary must be between -180 and 180.',
        ],

        'boundary_west'    => [
            'numeric' => 'The west boundary must be a valid number.',
            'between' => 'The west boundary must be between -180 and 180.',
        ],
    ],

    'event'                => [
        'name'                 => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'position'             => [
            "not_found" => 'Position is not exit.',
        ],

        'desktop_banner_image' => [
            'image'      => 'Desktop banner image must be image.',
            'mimes'      => 'Desktop banner image must have valid mimes[image/*].',
            'dimensions' => 'Desktop banner image must width 1300px & Height 90px.',
        ],

        'mobile_banner_image'  => [
            'image'      => 'Mobile banner image must be image.',
            'mimes'      => 'Mobile banner image must have valid mimes[image/*].',
            'dimensions' => 'Mobile banner image must width 400px & Height 90px.',
        ],

        'language_id'          => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],
    ],

    'contributor'          => [
        'name'          => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'profile_image' => [
            'image'      => 'Profile image image must be image.',
            'mimes'      => 'Profile image image must have valid mimes[image/*].',
            'dimensions' => 'Profile image image must have ratio 1:1 with min-width 512px & min-height 512px.',
        ],

    ],

    'news'                 => [
        'title'                             => [
            'required' => 'Title is required.',
            'string'   => 'Title must be string.',
            'max'      => 'Title max chars is 200.',
            'unique'   => 'Title must be unique.',
        ],

        'language_id'                       => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],
        'category_id'                       => [
            'required'  => 'Category is required.',
            "not_found" => 'Category is not exit.',
        ],

        'event_id'                          => [
            "not_found" => 'Event is not exit.',
        ],

        'location_id'                       => [
            "not_found" => 'Location is not exit.',
        ],

        'contributor_ids'                   => [
            "not_found" => 'Contributors is not exit.',
        ],

        'tag_ids'                           => [
            "not_found" => 'Tags is not exit.',
        ],

        'relevant_news_ids'                 => [
            "not_found" => 'Relevant News is not exit.',
        ],

        'related_news_ids'                  => [
            "not_found" => 'Related News is not exit.',
        ],

        'breaking_news_id'                  => [
            "not_found"            => 'Breaking news is not exit.',
            "already_sync_to_news" => 'Breaking news already sync.',
        ],

        'body'                              => [
            'required' => 'Body is required.',
        ],

        'video_url'                         => [
            'required' => 'Video url is required.',
            'url'      => 'Video url must be a url.',
        ],

        'feature_image_caption'             => [
            'required' => 'Feature image caption is required.',
        ],

        'upload_feature_image'              => [
            'image'      => 'Upload feature image must be image.',
            'mimes'      => 'Upload feature image must have valid mimes[image/*].',
            'dimensions' => 'Upload feature image must width 1280px & Height 720px.',
            "select_one" => 'Select one out of [Upload or select from media]',
        ],
        'selected_feature_image_url'        => [
            'image'      => 'Selected feature image must be image.',
            'mimes'      => 'Selected feature image must have valid mimes[image/*].',
            'dimensions' => 'Selected feature image must width 1280px & Height 720px.',
            "select_one" => 'Select one out of [Upload or select from media]',
        ],

        'upload_feature_image_mobile'       => [
            'image'      => 'Upload feature image mobile must be image.',
            'mimes'      => 'Upload feature image mobile must have valid mimes[image/*].',
            'dimensions' => 'Upload feature image mobile must min-width 400px & Height 225px.',
            "select_one" => 'Select one out of [Upload or select from media]',
        ],
        'selected_feature_image_mobile_url' => [
            'image'      => 'Selected feature image mobile must be image.',
            'mimes'      => 'Upload feature image mobile must have valid mimes[image/*].',
            'dimensions' => 'Upload feature image mobile must min-width 400px & Height 225px.',
            "select_one" => 'Select one out of [Upload or select from media]',
        ],

        'gallery_image_ids'                 => [
            'required' => 'Gallery Image is required.',
        ],

        'gallery_image'                     => [
            'order_column' => [
                'integer' => 'Gallery image order column must be a valid number.',
                'min'     => 'Gallery image order column must be at least 1.',
            ],

            'caption'      => [
                'string' => 'Gallery image caption must be valid text.',
                'max'    => 'Gallery image caption must not be greater than 255 characters.',
            ],

            'alt'          => [
                'string' => 'Gallery image alt text must be valid text.',
                'max'    => 'Gallery image alt text must not be greater than 255 characters.',
            ],

            'image'        => [
                'required' => 'Image is required.',
                'image'    => 'Image must be image.',
                'mimes'    => 'Image must have valid mimes[image/*].',
            ],
        ],

        'gallery_image_sequence'            => [
            'sequence' => [
                'required' => 'Sequence is required.',
                'min'      => 'Sequence must be at lease 1.',
                'integer'  => 'Sequence must be integer.',
                'distinct' => 'Sequence must be distinct. No duplicate allow.',
            ],
        ],

    ],

    'breaking_news'        => [
        'title'       => [
            'required' => 'Title is required.',
            'string'   => 'Title must be string.',
            'max'      => 'Title max chars is 200.',
            'unique'   => 'Title must be unique.',
        ],

        'language_id' => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],

        'news_id'     => [
            "not_found"    => 'News is not exit.',
            "already_sync" => 'Already sync to news.',
        ],
    ],

    'page'                 => [
        'title'          => [
            'required' => 'Title is required.',
            'string'   => 'Title must be string.',
            'max'      => 'Title max chars is 200.',
            'unique'   => 'Title must be unique.',
        ],

        'body'           => [
            'required' => 'Body is required.',
        ],

        'language_id'    => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],

        'parent_id'      => [
            'required'  => 'Parent is required.',
            "not_found" => 'Parent is not exit.',
        ],

        'default_use_as' => [
            'in' => 'Must be one out of [:values].',
        ],

    ],

    'menu'                 => [
        'name'         => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'language_id'  => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],

        'menu_type_id' => [
            'required'  => 'News type is required.',
            "not_found" => 'News type is not exit.',
        ],
    ],

    'menu_item'            => [
        'name'        => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],

        'language_id' => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],

        'parent_id'   => [
            "not_found" => 'Parent is not exit.',
        ],

        'model_type'  => [
            'required_if' => 'Model is required.',
        ],

        'model_id'    => [
            'required_if' => 'Model is required.',
            "not_found"   => 'Model is not exit.',
        ],

        'url'         => [
            'required_if' => 'Url is required.',
            'url'         => 'Url must be a url.',
        ],

        'position'    => [
            'integer' => 'Position must be a integer.',
        ],

    ],

    'theme'                => [
        'group' => [
            'required' => 'Group is required.',
            'string'   => 'Group must be a string.',
            'max'      => 'Group may not be greater than :max characters.',
            'not_exit' => 'Selected group does not exist.',
            'unique'   => 'A setting with this group and key already exists.',
        ],

        'label' => [
            'required' => 'Label is required.',
            'string'   => 'Label must be a string.',
            'max'      => 'Label may not be greater than :max characters.',
            'unique'   => 'A setting with this group and label already exists.',
        ],

        'type'  => [
            'required' => 'Type is required.',
            'string'   => 'Type must be a string.',
            'max'      => 'Type may not be greater than :max characters.',
            'not_exit' => 'Selected type does not exist.',
        ],

        'value' => [
            'required'     => 'Value is required.',
            'string'       => 'Value must be a string.',
            'max'          => 'Value may not be greater than :max characters.',
            'invalid_type' => 'Value does not match the selected setting type.',
        ],
    ],

    'google_adsence'       => [
        'name'     => [
            'required' => 'Group is required.',
            'string'   => 'Group must be a string.',
            'max'      => 'Group may not be greater than :max characters.',
        ],

        'slot_id'  => [
            'required' => 'Label is required.',
            'string'   => 'Label must be a string.',
            'max'      => 'Label may not be greater than :max characters.',
        ],

        'type'     => [
            'required' => 'Type is required.',
            'string'   => 'Type must be a string.',
            'max'      => 'Type may not be greater than :max characters.',
            'not_exit' => 'Selected type does not exist.',
        ],

        'position' => [
            'required' => 'Position is required.',
            'string'   => 'Position must be a string.',
            'max'      => 'Position may not be greater than :max characters.',
            'not_exit' => 'Selected position does not exist.',
        ],
    ],

    'survey'               => [
        'name'        => [
            'required' => 'Name is required.',
            'string'   => 'Name must be a string.',
            'max'      => 'Name may not be greater than :max characters.',
        ],
        'start_date'  => [
            'required' => 'Start date is required.',
            'date'     => 'Start date must be a valid date.',
        ],

        'end_date'    => [
            'required'       => 'End date is required.',
            'date'           => 'End date must be a valid date.',
            'after_or_equal' => 'End date must be after or equal to start date.',
        ],
        'language_id' => [
            'required'  => 'Language is required.',
            "not_found" => 'Language does not exit.',
        ],
    ],

    'survey_question'      => [
        'question' => [
            'required' => 'Question is required.',
            'string'   => 'Question must be a string.',
        ],
    ],

    'quiz'                 => [

        'name'              => [
            'required' => 'Quiz name is required.',
            'string'   => 'Quiz name must be a string.',
            'max'      => 'Quiz name may not be greater than 255 characters.',
        ],

        'language_id'       => [
            'required'  => 'Language is required.',
            'not_found' => 'Selected language was not found.',
        ],

        'brief'             => [
            'string' => 'Brief must be a string.',
        ],

        'start_date'        => [
            'required' => 'Start date is required.',
            'date'     => 'Start date must be a valid date.',
        ],

        'end_date'          => [
            'required'       => 'End date is required.',
            'date'           => 'End date must be a valid date.',
            'after_or_equal' => 'End date must be after or equal to the start date.',
        ],

        'is_active'        => [
            'required' => 'Current status is required.',
            'boolean'  => 'Current status must be true or false.',
        ],

        'enable_result'        => [
            'required' => 'Enable result is required.',
            'boolean'  => 'Enable result must be true or false.',
        ],

        'max_winner'        => [
            'required' => 'Max winner is required.',
            'min'  => 'Max winner at least 1.',
        ],

        'show_bellow_event' => [
            'required' => 'Show below event field is required.',
            'boolean'  => 'Show below event must be true or false.',
        ],

        'questions'         => [
            'required' => 'At least one question is required.',
            'array'    => 'Questions must be a valid array.',
            'min'      => 'At least one question is required.',
        ],

        'question'          => [
            'required' => 'Question is required.',
            'string'   => 'Question must be a string.',
        ],

        'answer_type'       => [
            'required' => 'Answer type is required.',
            'in'       => 'Answer type must be either Single or Multiple.',
        ],

        'point'             => [
            'required' => 'Point is required.',
            'numeric'  => 'Point must be a number.',
            'min'      => 'Point must be at least 0.',
        ],

        'position'          => [
            'integer' => 'Position must be an integer.',
            'min'     => 'Position must be at least 1.',
        ],

        'options'           => [
            'required' => 'At least one options are required.',
            'array'    => 'Options must be a valid array.',
            'min'      => 'At least one options are required.',
        ],

        'option'            => [
            'required' => 'Option is required.',
            'string'   => 'Option must be a string.',
        ],

        'is_correct'        => [
            'required' => 'Please specify whether the option is correct.',
            'boolean'  => 'Correct value must be true or false.',
        ],

        'answer'            => [
            'single'   => 'A Single answer question must have exactly one correct option.',
            'multiple' => 'A Multiple answer question must have at least one correct option.',
        ],

    ],

    'quiz_question'        => [

        'question'    => [
            'required' => 'Question is required.',
            'string'   => 'Question must be a string.',
        ],

        'answer_type' => [
            'required' => 'Answer type is required.',
            'in'       => 'Answer type must be either Single or Multiple.',
        ],

        'point'       => [
            'required' => 'Point is required.',
            'numeric'  => 'Point must be a number.',
            'min'      => 'Point must be at least 0.',
        ],

        'position'    => [
            'integer' => 'Position must be an integer.',
            'min'     => 'Position must be at least 1.',
        ],

        'options'     => [
            'required' => 'At least one options are required.',
            'array'    => 'Options must be a valid array.',
            'min'      => 'At least one options are required.',
        ],

        'option'      => [
            'required' => 'Option is required.',
            'string'   => 'Option must be a string.',
        ],

        'is_correct'  => [
            'required' => 'Please specify whether the option is correct.',
            'boolean'  => 'Correct value must be true or false.',
        ],

        'answer'      => [
            'single'   => 'A Single answer question must have exactly one correct option.',
            'multiple' => 'A Multiple answer question must have at least one correct option.',
        ],

    ],

    'quiz_question_option' => [

        'option'     => [
            'required' => 'Option is required.',
            'string'   => 'Option must be a string.',
        ],

        'is_correct' => [
            'required' => 'Please specify whether the option is correct.',
            'boolean'  => 'Correct value must be true or false.',
        ],

        'position'   => [
            'integer' => 'Position must be an integer.',
            'min'     => 'Position must be at least 1.',
        ],

    ],

];

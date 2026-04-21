<?php

return [
    "api"      => [
        'timeout' => 'Api responce timeout.',
    ],

    'auth'     => [
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

    'user'     => [
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

        'user_role_id'          => [
            'required'               => 'Please select user role.',
            "not_found"              => 'Selected user role does not exit.',
            "do_not_have_permission" => 'Please select another user role. You can not create user using this user role.',
        ],

        'profile_image'         => [
            'image'      => 'Upload feature image must be image.',
            'mimes'      => 'Upload feature image is mimes must be one out of [jpg,jpeg,png,webp].',
            'dimensions' => 'Upload feature image must be dimensions ratio is 1:1 and  size 512x512px.',
        ],
    ],

    'language' => [
        'name' => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],
        'code' => [
            'required' => 'Code is required.',
            'string'   => 'Code must be string.',
            'max'      => 'Code max chars is 200.',
            'unique'   => 'Code must be unique.',
        ],
    ],

    'category'          => [
        'name'                               => [
            'required' => 'Name is required.',
            'string'   => 'Name must be string.',
            'max'      => 'Name max chars is 200.',
            'unique'   => 'Name must be unique.',
        ],


        'parent_id'                          => [
            'required'  => 'Parent is required.',
            "not_found" => 'Parent is not exit.',
        ],

        'language_id'                          => [
            'required'  => 'Language is required.',
            "not_found" => 'Language is not exit.',
        ],
    ],
];

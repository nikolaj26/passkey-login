<?php

use Webauthn\AuthenticatorSelectionCriteria;

return [
    'multi_step' => false,

    'matching_email' => false,

    'open_on_page_load' => false,

    'register_options' => [
        'rp' => [
            'name' => env('APP_NAME'),
            'id' => parse_url(env('APP_URL'), PHP_URL_HOST)
        ],
        'user' => [
            'name' => 'email',
            'id' => 'id',
            'displayName' => 'first_name'
        ],
        'challenge' => \Illuminate\Support\Str::random(),
        'authenticator_selection' => [
            'authenticator_attachment' => AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            'resident_key' => AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_NO_PREFERENCE
        ]
    ],

    'authenticate_options' => [
        'challenge' => \Illuminate\Support\Str::random(),
        'rp_id' => parse_url(env('APP_URL'), PHP_URL_HOST),
    ]
];

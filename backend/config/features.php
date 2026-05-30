<?php

return [
    /*
    |--------------------------------------------------------------------------
    | V3 Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control rollout of V3 products per governorate.
    | Set in .env or toggle via Admin UI.
    |
    */
    'financing_enabled' => env('FINANCING_ENABLED', false),
    'education_enabled' => env('EDUCATION_ENABLED', false),
    'humanitarian_enabled' => env('HUMANITARIAN_ENABLED', false),
    'open_api_enabled' => env('OPEN_API_ENABLED', false),
    'bnpl_enabled' => env('BNPL_ENABLED', false),
    'financing_governorates' => env('FINANCING_GOVERNORATES', ''),
    'education_governorates' => env('EDUCATION_GOVERNORATES', ''),
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | Public images are stored on the Supabase Storage disk. Tests may swap
    | this disk with Storage::fake() without touching the real bucket.
    |
    */

    'disk' => env('MEDIA_DISK', 'supabase'),

    /*
    |--------------------------------------------------------------------------
    | Upload Validation
    |--------------------------------------------------------------------------
    |
    | Conservative server-side limits applied to every uploaded image. They
    | are intentionally explicit so controllers and form requests share the
    | exact same rules.
    |
    */

    'max_kilobytes' => (int) env('MEDIA_MAX_KILOBYTES', 5120),

    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],

    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ],

    'max_width' => (int) env('MEDIA_MAX_WIDTH', 4000),

    'max_height' => (int) env('MEDIA_MAX_HEIGHT', 4000),

    /*
    |--------------------------------------------------------------------------
    | Product Image Limit
    |--------------------------------------------------------------------------
    |
    | A configurable safety limit, not a business rule from the documents.
    | The final limit still needs an explicit decision.
    |
    */

    'max_product_images' => (int) env('MEDIA_MAX_PRODUCT_IMAGES', 10),

    /*
    |--------------------------------------------------------------------------
    | Storage Folders
    |--------------------------------------------------------------------------
    |
    | Object keys inside the public bucket. Entities append their UUID so
    | replacement and cleanup stay predictable.
    |
    */

    'folders' => [
        'site' => 'site',
        'product' => 'products',
        'banner' => 'banners',
        'wallet' => 'wallets',
        'healthcheck' => 'healthchecks',
    ],

];

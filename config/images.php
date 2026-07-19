<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image conversions
    |--------------------------------------------------------------------------
    |
    | Each entry generates a WebP variant alongside the original upload,
    | named "{filename}-{key}.webp" in the same directory. Widths are
    | upper bounds — images narrower than this are left at their own size
    | (scaleDown never upscales).
    |
    */

    'conversions' => [
        'thumb' => ['width' => 400, 'quality' => 75],
        'card' => ['width' => 800, 'quality' => 78],
        'hero' => ['width' => 1600, 'quality' => 78],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed upload mime types
    |--------------------------------------------------------------------------
    |
    | Animated GIFs are intentionally excluded — they are the single
    | heaviest asset type on the site and every current use case (portfolio
    | grid, logos, cards) only needs a static image.
    |
    */

    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],

];

<?php

/*
| Brand and business settings for the clinic. The demo brand is fictional.
| Change the clinic here or in .env, never in components (plan.md §3).
*/

return [

    // Organization slug the public website belongs to. This is a database key
    // and not a display string: AppServiceProvider looks the organization up by
    // it on every request, so changing it here without also renaming the row
    // leaves the lookup returning null and the site down.
    'organization' => env('CLINIC_ORGANIZATION', 'irish'),

    'name' => env('CLINIC_NAME', 'Irish Aesthetics and Beauty Lounge'),
    'short_name' => env('CLINIC_SHORT_NAME', 'Irish'),
    'tagline' => env('CLINIC_TAGLINE', 'Beauty that enhances who you already are.'),

    /*
     * The brand mark, as a path inside public/ with no leading slash, so
     * asset() resolves it against the install's own base URL. A leading slash
     * would escape the subdirectory this runs in and 404 on every page.
     *
     * Both files are generated from the clinic's master logo by
     * `php scripts/build-logo.php <path>`; they are not edited by hand. WebP is
     * offered first and the PNG is the fallback, so a browser that will not
     * take it still gets the mark rather than a broken image icon.
     */
    'logo' => env('CLINIC_LOGO', 'media/brand/logo.webp'),
    'logo_fallback' => env('CLINIC_LOGO_FALLBACK', 'media/brand/logo.png'),

    /*
     * Brand palette. Mirrors the @theme tokens in resources/css/app.css; the CSS
     * is what the components actually paint with, these are the values handed
     * to the browser in the shared `clinic` prop (JSON-LD, meta, print styles).
     *
     * These are the owner's colours and are reproduced exactly.
     *
     * `plum` is the dark field the light pinks sit on. Signature rose carries
     * white text at only 2.5:1, so it is a fill and a border, never a
     * background for text. `rose_ink` is the one derived shade: deep rose fails
     * AA as link text at 3.7:1, so links use this instead.
     */
    'colors' => [
        'primary' => '#F47FA4',
        'primary_dark' => '#D9577F',
        'blush' => '#FCE8EE',
        'ivory' => '#FFFDFC',
        'plum' => '#3B2430',
        'champagne' => '#C9A46C',
        'text' => '#51454A',
        'soft_rose' => '#FFF4F7',
        'rose_ink' => '#A83257',
    ],

    'contact' => [
        'address' => env('CLINIC_ADDRESS', '28 Amorsolo Street, Legaspi Village, Makati City'),
        'phone' => env('CLINIC_PHONE', '0917 177 7201'),
        'email' => env('CLINIC_EMAIL', 'hello@irish.test'),
    ],

    'social' => [
        'facebook' => env('CLINIC_FACEBOOK'),
        'instagram' => env('CLINIC_INSTAGRAM'),
        'tiktok' => env('CLINIC_TIKTOK'),
    ],

    'currency' => env('CLINIC_CURRENCY', 'PHP'),
    'currency_symbol' => env('CLINIC_CURRENCY_SYMBOL', '₱'),
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),

    'hours' => [
        'mon-fri' => '10:00-20:00',
        'sat' => '10:00-18:00',
        'sun' => 'closed',
    ],

    // Default for each module flag (Laravel Pennant, per organization).
    'modules' => [
        'booking' => true,
        'crm' => true,
        'pos' => true,
        'inventory' => true,
        'accounting' => true,
        'payroll' => true,
        'ai' => true,
        'multi_branch' => true,
    ],

];

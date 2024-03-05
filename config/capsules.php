<?php

use App\Support\Masking\PrefixNoteMasker;

return [

    /*
    |--------------------------------------------------------------------------
    | Note masking
    |--------------------------------------------------------------------------
    |
    | How a still-sealed note is rendered to its owner. `masker` must name a
    | class implementing App\Support\Masking\NoteMasker; it is resolved from
    | the container, so a replacement may take constructor dependencies.
    |
    */

    'masker' => PrefixNoteMasker::class,

    'preview_length' => (int) env('CAPSULE_PREVIEW_LENGTH', 4),

    /*
    |--------------------------------------------------------------------------
    | Note size
    |--------------------------------------------------------------------------
    |
    | Upper bound on a stored note. The column is TEXT, so without a bound a
    | single request can push 64 KB per capsule into the database.
    |
    */

    'max_note_length' => (int) env('CAPSULE_MAX_NOTE_LENGTH', 5000),

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    |
    | `GET /users/{user}/message-capsules` returns the full collection unless
    | the caller opts into pagination with ?per_page= or ?page=. `max_per_page`
    | caps what a caller may ask for.
    |
    */

    'default_per_page' => (int) env('CAPSULE_DEFAULT_PER_PAGE', 15),

    'max_per_page' => (int) env('CAPSULE_MAX_PER_PAGE', 100),

];

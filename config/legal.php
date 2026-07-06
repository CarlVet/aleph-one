<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal documents
    |--------------------------------------------------------------------------
    |
    | Terms of Service and Privacy Policy live outside this codebase (they are
    | published on the marketing site). The app only links to them and records
    | which version a user accepted at registration.
    |
    | When both URLs are empty the legal links and the registration consent
    | checkbox are hidden, and acceptance is not required. Set the URLs on the
    | hosted environment to enable them. Bump the *_version values whenever the
    | published documents change so acceptances stay auditable.
    |
    */

    'terms_url' => env('LEGAL_TERMS_URL'),

    'privacy_url' => env('LEGAL_PRIVACY_URL'),

    'terms_version' => env('LEGAL_TERMS_VERSION'),

    'privacy_version' => env('LEGAL_PRIVACY_VERSION'),

];

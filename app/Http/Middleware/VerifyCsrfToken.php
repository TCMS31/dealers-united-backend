<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Fortify's credential endpoints are exempt.
     *
     * They are the only session-backed routes in the application and they are
     * authenticated by the credentials in the request body, not by the session
     * cookie, so a CSRF token adds nothing: an attacker who can supply a valid
     * email and password does not need the victim's browser. Every other
     * cookie-authenticated route is protected — the middleware used to be
     * commented out of the `web` group entirely.
     *
     * The capsule endpoints do not appear here because they run in the
     * stateless `api` group and are authenticated by a Sanctum bearer token.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/v1/register',
        'api/v1/login',
        'api/v1/logout',
        'api/v1/forgot-password',
        'api/v1/reset-password',
    ];
}

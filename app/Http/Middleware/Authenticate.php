<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * This application is an API: there is no login page to redirect to.
     * Returning null makes the parent throw an AuthenticationException, which
     * the exception handler renders as a 401.
     *
     * The previous implementation returned `route('login')` for any request
     * that did not explicitly ask for JSON. Fortify is configured with
     * `views => false`, so no route named `login` is registered and the
     * redirect raised "Route [login] not defined" — a 500 instead of a 401.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}

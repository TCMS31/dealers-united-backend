<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every capsule route is nested under `/users/{user}`. This refuses the
 * request unless `{user}` is the authenticated user, so one account can never
 * address another account's collection.
 *
 * It reads the route parameter directly. The previous `$request->user` went
 * through `Request::__get`, which looks in the *request body* first: a payload
 * carrying a `user` key shadowed the bound model and the comparison fatalled
 * with "Call to a member function getKey() on string" — a 500 any client could
 * trigger with one extra JSON field.
 */
class EnsureRequestUserAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user();
        $routeUser = $request->route('user');

        if ($authenticated instanceof User
            && $routeUser instanceof User
            && $authenticated->is($routeUser)) {
            return $next($request);
        }

        return response()->json(
            ['message' => 'You are not authorized to act on behalf of this user.'],
            Response::HTTP_FORBIDDEN
        );
    }
}

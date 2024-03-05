<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards Fortify's credential endpoints against an already-signed-in caller.
 *
 * It used to redirect to RouteServiceProvider::HOME ("/home"), a route this
 * application never defines, so an already-authenticated POST to /login
 * answered with a redirect to a 404. There is no interface to send anyone to,
 * so the API says so instead.
 */
class RedirectIfAuthenticated
{
    /**
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return response()->json(
                    ['message' => 'You are already authenticated.'],
                    Response::HTTP_CONFLICT
                );
            }
        }

        return $next($request);
    }
}

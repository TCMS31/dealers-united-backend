<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * The API speaks one error shape: `{"message": "...", "errors": {...}}` —
 * Laravel's own validation shape, which is what HTTP clients already parse.
 *
 * Two things are customised here.
 *
 * 1. Anything under `api/*` answers with JSON whether or not the caller sent
 *    an `Accept` header. Without this, an unauthenticated request with no
 *    Accept header fell through to `redirect()->guest(route('login'))`, and
 *    Fortify registers no route named `login` when views are disabled — so a
 *    missing token produced a 500 "Route [login] not defined" instead of 401.
 *
 * 2. Authorisation failures are 403. The previous handler rendered them as
 *    401, which tells an HTTP client its credentials are gone: opening a
 *    capsule an hour early logged the user out of the Vue app.
 */
class Handler extends ExceptionHandler
{
    /**
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // AuthorizationException has already been converted to
        // AccessDeniedHttpException by prepareException() at this point.
        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
            return $this->apiError(
                $request,
                $e->getMessage() ?: 'This action is unauthorized.',
                Response::HTTP_FORBIDDEN
            );
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            return $this->apiError($request, 'Resource not found.', Response::HTTP_NOT_FOUND);
        });
    }

    /**
     * Force JSON for the API surface regardless of the Accept header.
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }

    /**
     * @return JsonResponse|null null hands the exception back to the default
     *                           renderer for non-API requests.
     */
    private function apiError(Request $request, string $message, int $status): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        return response()->json(['message' => $message], $status);
    }
}

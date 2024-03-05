<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify owns the credential handling; this provider replaces its
 * browser-oriented responses with the JSON + bearer-token contract the API
 * actually speaks.
 */
class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegisterResponse::class, fn () => $this->tokenResponse());
        $this->app->bind(LoginResponse::class, fn () => $this->tokenResponse());

        $this->app->bind(LogoutResponse::class, function () {
            return new class implements LogoutResponse
            {
                public function toResponse($request): JsonResponse
                {
                    // Fortify has already logged the session guard out by the
                    // time this runs, so $request->user() is null. The old
                    // implementation called ->currentAccessToken() on it and
                    // fatalled, then redirected to '/'.$request->alias — an
                    // undefined property — instead of answering the API call.
                    return response()->json(null, Response::HTTP_NO_CONTENT);
                }
            };
        });
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /**
     * Register and log in answer the same way: the account, plus a Sanctum
     * token the client sends as `Authorization: Bearer …` from then on.
     */
    private function tokenResponse(): object
    {
        return new class implements LoginResponse, RegisterResponse
        {
            public function toResponse($request): JsonResponse
            {
                /** @var User $user */
                $user = $request->user() ?? auth()->user();

                return response()->json([
                    'user' => (new UserResource($user))->resolve(),
                    'token' => $user->createToken($user->email)->plainTextToken,
                ]);
            }
        };
    }
}

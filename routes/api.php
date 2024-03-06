<?php

use App\Http\Controllers\MessageCapsuleController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider under the `api` middleware group and the
| `api/v1` prefix. Authentication is a Sanctum bearer token issued by the
| Fortify register/login endpoints (see FortifyServiceProvider).
|
| Capsule routes are nested under `/users/{user}` and carry
| `request.user.authenticated`, which rejects any attempt to address another
| account's collection before the controller or policy is reached.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return (new UserResource($request->user()))->resolve();
})->name('api.user');

Route::middleware(['auth:sanctum', 'request.user.authenticated'])
    ->prefix('users/{user}')
    ->name('users.message-capsules.')
    ->group(function () {
        Route::get('message-capsules', [MessageCapsuleController::class, 'index'])->name('index');
        Route::post('message-capsules', [MessageCapsuleController::class, 'store'])->name('store');
        Route::get('message-capsules/{message_capsule}', [MessageCapsuleController::class, 'show'])->name('show');
        Route::put('message-capsules/{message_capsule}/open', [MessageCapsuleController::class, 'open'])->name('open');
    });

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| This application has no interface of its own. The root route is a service
| descriptor: enough for a load balancer health check and for a developer who
| has just started the container to see that it is alive and which version of
| the API it serves.
|
*/

Route::get('/', function () {
    return response()->json([
        'service' => config('app.name'),
        'status' => 'ok',
        'api' => '/'.App\Providers\RouteServiceProvider::API_PREFIX,
    ]);
})->name('health');

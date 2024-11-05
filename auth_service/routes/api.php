<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


use App\Http\Middleware\JwtMiddleware;
/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'actionLogin'])->name('login');
    Route::post('/register', [AuthController::class, 'actionRegister'])->name('register');
    Route::get('/roles', [AuthController::class, 'actionGetRoles'])->name('Roles');




    Route::get("/test", [AuthController::class, 'actionTest'])->name("test");
    Route::middleware([JwtMiddleware::class])->group(function () {


        Route::post('/logout', [AuthController::class, 'actionLogout'])->name('logout'); //->middleware('auth:api')
        Route::post('/refresh', [AuthController::class, 'actionRefresh'])->name('refresh'); //->middleware('auth:api')
        Route::post('/me', [AuthController::class, 'actionMe'])->name('me'); //->middleware('auth:api')
    });
});

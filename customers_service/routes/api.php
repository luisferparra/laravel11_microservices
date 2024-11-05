<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomersController;
use App\Http\Middleware\JwtMiddleware;


Route::group([
    'middleware' => 'api',
    'prefix' => 'customers'
], function ($router) {
    Route::get('/list', [CustomersController::class, 'actionCustomersList'])->middleware('JwtMiddleware:CompanyAdmin:read|execute,SuperAdmin'); //CompanyAdmin:read|exececute,SuperAdmin
    Route::post('/create', [CustomersController::class, 'actionCreateCustomer'])->middleware('JwtMiddleware:CompanyAdmin:read|write|execute,SuperAdmin');

});

<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::controller(UserController::class)->group(function() {
    Route::get('/user', 'getUsers')->name('get-users');
    Route::get('/user/{id}', 'getUserById')->name('get-user-by-id');
    Route::post('/login', 'login')->name('login');
    Route::post('/logout', 'logout')->name('logout');

    Route::get('/refresh-token/{data}', 'getRefreshToken')->name('refresh-token');
    Route::post('/refresh-token', 'refreshTokenStore')->name('refresh-token.create');
});

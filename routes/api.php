<?php

use App\Http\Controllers\User\UserController;
use App\Http\Middleware\VerifyToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// USER
Route::controller(UserController::class)->group(function() {
    Route::prefix('user')->group(function() {
        Route::name('user')->group(function() {
            Route::post('/login', 'login')->name('.login');
            Route::post('/refresh-token', 'refreshToken')->name('.refresh-token');

            Route::middleware(VerifyToken::class)->group(function() {
                Route::get('/', 'getUsers')->name('.user');
                Route::post('/logout', 'logout')->name('.user');
            });
        });
    });
});

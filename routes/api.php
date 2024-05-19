<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LetterListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// CATEGORY
Route::controller(CategoryController::class)->group(function() {
    Route::prefix('category')->group(function() {
        Route::name('category')->group(function() {
            Route::get('/', 'index');
            Route::get('/{data}', 'detail')->name('.detail');
            Route::get('/type/{data}', 'getByType')->name('.type');
            Route::post('/', 'create')->name('.create');
            Route::put('/{data}', 'update')->name('.update');
            Route::delete('/{data}', 'delete')->name('.delete');
        });
    });
});

// LETTERS LIST
Route::controller(LetterListController::class)->group(function() {
    Route::get('/', 'index');
    Route::get('/new-number/company/{data}', 'getNewCompanyLetterNumber')->name('new-company-letter-number');
    Route::post('/', 'storeSurat')->name('store');
});

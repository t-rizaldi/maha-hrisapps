<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndonesiaRegionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::controller(IndonesiaRegionController::class)->group(function() {
    Route::get('/all-province', 'getAllProvince')->name('get-all-province');
    Route::get('/all-regency/{id}', 'getAllRegencyByIDProvince')->name('get-all-regency-by-id-province');
    Route::get('/all-district/{id}', 'getAllDistrictByIDRegency')->name('get-all-district-by-id-regency');
    Route::get('/all-village/{id}', 'getAllVillageByIDDistrict')->name('get-all-village-by-id-district');
    Route::get('/province/{id}', 'getProvinceByID')->name('get-province-by-id');
    Route::get('/regency/{id}', 'getRegencyByID')->name('get-regency-by-id');
    Route::get('/district/{id}', 'getDistrictByID')->name('get-district-by-id');
    Route::get('/village/{id}', 'getVillageByID')->name('get-village-by-id');

});

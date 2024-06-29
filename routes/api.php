<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AttendanceController::class)->group(function() {
    // Attendance
    Route::post('/', 'storeAttendance')->name('store');
    Route::get('/employee-history/{data1}/{data2}/{data3}', 'employeeAttendanceHistory')->name('employee-history');

    // Overtime
    Route::prefix('overtime')->group(function() {
        Route::name('overtime')->group(function() {
            // Overtime Data
            Route::get('/{data}', 'getOvertimeByEmployeeId');
            Route::post('/', 'storeOvertime')->name('.store');
            Route::put('/{data}/{data1}', 'updateOvertime')->name('.update');
            Route::delete('/{data}/{data1}', 'deleteOvertime')->name('.delete');
            Route::post('/submit', 'submitOvertime')->name('.submit');
            Route::post('/reject', 'rejectOvertime')->name('.reject');
            Route::post('/approve', 'approveOvertime')->name('.approve');
            Route::get('/list-by-approve/{data}', 'getOvertimeByApprover')->name('.list-by-approve');
            Route::post('/order', 'overtimeOrderStore')->name('.order.store');
            // Overtime Phot
            Route::prefix('photo')->group(function() {
                Route::name('.photo')->group(function() {
                    Route::post('/{data}/{data1}', 'storeOvertimePhoto')->name('.store');
                });
            });
        });
    });
});

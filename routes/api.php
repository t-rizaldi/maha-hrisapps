<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceWorkerController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SickController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AttendanceController::class)->group(function() {
    // Attendance
    Route::post('/', 'storeAttendance')->name('store');
    Route::get('/employee-history/{data1}/{data2}/{data3}', 'employeeAttendanceHistory')->name('employee-history');
    Route::get('/history/{data}/{data1}', 'attendanceHistory')->name('history');

    // Overtime
    Route::prefix('overtime')->group(function() {
        Route::name('overtime')->group(function() {
            // Overtime Data
            Route::get('/{data}', 'getOvertimeByEmployeeId');
            Route::get('/{data}/{data1}', 'getAllEmployeeOvertimeBydate')->name('.all-employee-by-date');
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

    // Monitoring Attendance
    Route::get('/today-statistic', 'todayStatistics');
    Route::prefix('history')->group(function () {
        Route::get('/{data}/{data1}', 'attendanceHistory')->name('history');
        Route::get('/overtime/{startDate}/{endDate}', 'overtimeHistory');
        Route::get('/late/{startDate}/{endDate}', 'lateHistory');
        Route::get('/not_absent_home/{startDate}/{endDate}', 'notAbsentHomeHistory');
        Route::get('/permit/{startDate}/{endDate}', 'permitHistory');
        Route::get('/sick/{startDate}/{endDate}', 'sickHistory');
        Route::get('/leave/{startDate}/{endDate}', 'leaveHistory');
        Route::get('/not_absent/{startDate}/{endDate}', 'notPresentHistory');
    });
});

Route::controller(AttendanceWorkerController::class)->group(function() {
    Route::prefix('worker')->group(function() {
        Route::post('/', 'storeAttendance');
    });
});

// Permit
Route::controller(PermitController::class)->group(function () {
    Route::prefix('permit')->group(function () {
        // Permit Type
        Route::prefix('type')->group(function () {
            Route::get('/all', 'getAllPermitType');
            Route::get('/{id}', 'getPermitTypeByID');
            Route::get('/', 'getPermitByType');
            Route::post('/', 'storePermitType');
            Route::put('/{id}', 'updatePermitType');
            Route::delete('/{id}', 'deletePermitType');
        });

        // Permit Application
        Route::prefix('application')->group(function () {
            Route::get('/', 'getAllPermit');
            Route::get('/{id}', 'getPermitByID');
            Route::get('/employee/{id}', 'getPermitByEmployeeID');
            Route::get('/list-by-approver/{id}', 'getAllPermitByApprover');
            Route::post('/', 'storePermit');
            Route::post('/update/{id}', 'updatePermit');
            Route::delete('/{id}', 'deletePermit');
            Route::post('/approve', 'approvePermit');
            Route::post('/reject', 'rejectPermit');
        });
    });
});

//Sick
Route::controller(SickController::class)->group(function () {
    Route::prefix('sick')->group(function () {
        // Sick Application
        Route::prefix('application')->group(function () {
            Route::get('/', 'getAllSick');
            Route::get('/{id}', 'getSickByID');
            Route::get('/employee/{id}', 'getSickByEmployeeID');
            Route::get('/list-by-approver/{id}', 'getAllSickByApprover');
            Route::post('/', 'storeSick');
            Route::post('/update/{id}', 'updateSick');
            Route::delete('/{id}', 'deleteSickByID');
            Route::post('/approve', 'approveSick');
            Route::post('/reject', 'rejectSick');
        });
    });
});

//Leave
Route::controller(LeaveController::class)->group(function () {
    Route::prefix('leave')->group(function () {
        // Leave Application
        Route::prefix('application')->group(function () {
            Route::get('/', 'getAllLeave');
            Route::get('/{id}', 'getLeaveByID');
            Route::get('/employee/{id}', 'getLeaveByEmployeeID');
            Route::get('/list-by-approver/{id}', 'getAllLeaveByApprover');
            Route::post('/', 'storeLeave');
            Route::post('/update/{id}', 'updateLeave');
            Route::delete('/{id}', 'deleteLeaveByID');
            Route::post('/approve', 'approveLeave');
            Route::post('/reject', 'rejectLeave');
        });
    });
});

// Holiday
Route::controller(HolidayController::class)->group(function () {
    Route::prefix('holiday')->group(function () {
        Route::get('/', 'getAllHolidays');
        Route::get('/{id}', 'getHolidayById');
        Route::get('/status/{status}', 'getHolidayByStatus');
        Route::get('/outside/{year}', 'getNationalHolidays');
        Route::post('/', 'storeOrUpdateHoliday');
        Route::delete('/{id}', 'deleteHoliday');
    });
});

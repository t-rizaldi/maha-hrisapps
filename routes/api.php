<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\JobTitleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AUTH
Route::controller(AuthController::class)->group(function() {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
    Route::post('/logout', 'logout')->name('logout');
    Route::post('/refresh-token', 'refreshTokenStore')->name('refresh-token.store');
    Route::get('/refresh-token/{data}', 'getRefreshToken')->name('refresh-token');
});

// DEPARTMENT
Route::controller(DepartmentController::class)->group(function() {
    Route::prefix('department')->group(function() {
        Route::name('department')->group(function() {
            Route::get('/', 'index');
            Route::get('/{data}', 'detail')->name('.detail');
            Route::post('/', 'store')->name('.create');
            Route::put('/{data}', 'update')->name('.update');
            Route::delete('/{data}', 'delete')->name('.delete');
        });
    });
});

// BRANCH
Route::controller(BranchController::class)->group(function() {
    Route::prefix('branch')->group(function() {
        Route::name('branch')->group(function() {
            Route::get('/{data}/detail', 'detail')->name('.detail');
            // parent branch
            Route::get('/', 'index');
            Route::post('/', 'storeParentBranch')->name('.create');
            Route::put('/{data}', 'updateParentBranch')->name('.update');
            Route::delete('/{data}', 'deleteParentBranch')->name('.delete');

            // children branch
            Route::prefix('children')->group(function() {
                Route::name('.children')->group(function() {
                    Route::get('/', 'getAllChildrenBranch');
                    Route::get('/parent/{data}', 'getAllChildrenByParentCode')->name('.parent');
                    Route::post('/', 'storeChildrenBranch')->name('.create');
                    Route::put('/{data}', 'updateChildrenBranch')->name('.update');
                    Route::delete('/{data}', 'deleteChildrenBranch')->name('.delete');
                });
            });

        });
    });
});

// JOB TITLE
Route::controller(JobTitleController::class)->group(function() {
    Route::prefix('job-title')->group(function() {
        Route::name('job-title')->group(function() {
            Route::get('/', 'index');
            Route::get('/{data}', 'detail')->name('.detail');
            Route::get('/department/{data}', 'getAllByDepartment')->name('.department');
            Route::get('/type/{data}', 'getAllByType')->name('.type');
            Route::get('/type/{data}/{data1}', 'getAllByTypeRole')->name('.type.role');
            Route::post('/', 'create')->name('.create');
            Route::put('/{data}', 'update')->name('.update');
            Route::delete('/{data}', 'delete')->name('.delete');
        });
    });
});

// EMPLOYEE
Route::controller(EmployeeController::class)->group(function() {
    Route::post('/verify-register', 'verifyRegister')->name('verify-register');
    Route::get('/', 'index')->name('get-all-employees');
    Route::get('/{data}', 'getEmployeeById')->name('get-employee');

    Route::post('/employee-biodata', 'storeEmployeeBiodata')->name('employee-biodata.store');
    Route::put('/employee-biodata/{data}', 'updateEmployeeBiodata')->name('employee-biodata.update');
});

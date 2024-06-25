<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\WorkHourController;
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

// SKILL
Route::controller(SkillController::class)->group(function() {
    Route::prefix('skill')->group(function() {
        Route::name('skill')->group(function() {
            Route::get('/get-from-lightcast', 'getAllFromLightcast')->name('.get-from-lightcast');
            Route::get('/', 'index');
            Route::get('/{data}', 'getById')->name('.detail');
            Route::post('/', 'store')->name('.store');
            Route::put('/{data}', 'update')->name('.update');
            Route::delete('/{data}', 'delete')->name('.delete');
        });
    });
});

// WORK HOUR
Route::controller(WorkHourController::class)->group(function() {
    Route::prefix('work-hour')->group(function() {
        Route::name('work-hour')->group(function() {
            Route::get('/', 'index');
            Route::get('/{data}', 'detail')->name('.detail');
            Route::post('/', 'store')->name('.store');
            Route::put('/{data}', 'update')->name('.update');
            Route::delete('/{data}', 'delete')->name('.delete');
        });
    });
});

// EMPLOYEE
Route::controller(EmployeeController::class)->group(function() {
    Route::post('/verify-register', 'verifyRegister')->name('verify-register');
    Route::put('/reject-register', 'rejectRegister')->name('reject-register');
    Route::put('/verify-data/{data}', 'verifyData')->name('verify-data');
    Route::put('/reject-data', 'rejectData')->name('reject-data')   ;
    Route::get('/', 'index')->name('get-all-employees');
    Route::get('/{data}', 'getEmployeeById')->name('get-employee');

    // BIODATA
    Route::get('/employee-biodata/{data}', 'getEmployeeBiodata')->name('employee-biodata');
    Route::post('/employee-biodata', 'storeEmployeeBiodata')->name('employee-biodata.store');
    //EDUCATION
    Route::get('/employee-education/{data}', 'getEmployeeEducation')->name('employee-education');
    Route::post('/employee-education', 'storeEmployeeEducation')->name('employee-education.store');
    // FAMILY
    Route::get('/employee-family/{data}', 'getEmployeeFamily')->name('employee-family');
    Route::post('/employee-family', 'storeEmployeeFamily')->name('employee-family.store');
    Route::put('/employee-family/{data}', 'updateEmployeeFamily')->name('employee-family.update');
    // MARITAL
    Route::put('/employee-marital/{data}', 'updateEmployeeMarital')->name('employee-marital.update');
    // SIBLING
    Route::get('/employee-sibling/{data}', 'getAlSiblingByEmployeeId')->name('employee-sibling');
    Route::post('/employee-sibling', 'createEmployeeSibling')->name('employee-sibling.store');
    Route::put('/employee-sibling/{data}/{data1}', 'updateEmployeeSibling')->name('employee-sibling.update');
    Route::delete('/employee-sibling/{data}/{data1}', 'deleteEmployeeSibling')->name('employee-sibling.delete');
    // CHILD
    Route::get('/employee-children/{data}', 'getAllChildrenByEmployeeId')->name('employee-children');
    Route::get('/employee-children/{data}/{data1}', 'getChildById')->name('employee-children.detail');
    Route::post('/employee-children', 'createEmployeeChild')->name('employee-children.store');
    Route::put('/employee-children/{data}/{data1}', 'updateEmployeeChild')->name('employee-children.update');
    Route::delete('/employee-children/{data}/{data1}', 'deleteEmployeeChild')->name('employee-children.delete');
    // DOCUMENT
    Route::get('/employee-document/{data}', 'getEmployeeDocument')->name('employee-document');
    Route::post('/employee-document', 'createEmployeeDocument')->name('employee-document.store');
    Route::post('/employee-document/{data}', 'updateEmployeeDocument')->name('employee-document.update');
    Route::delete('/employee-document/{data}', 'deleteEmployeeDocument')->name('employee-document.delete');
    //SIGNATURE
    Route::get('/employee-signature/{data}', 'getEmployeeSignature')->name('employee-signature');
    Route::post('/employee-signature/{data}', 'createEmployeeSignature')->name('employee-signature.create');
    // SKILL
    Route::get('/employee-skill/{data}', 'getEmployeeSkill')->name('employee-skill');
    Route::post('/employee-skill/{data}', 'updateEmployeeSkill')->name('employee-skill.update');
    // WORK HOUR
    Route::get('/employee-work-hour/{data}', 'getEmployeeWorkHour')->name('employee-work-hour');
    Route::post('/employee-work-hour', 'createEmployeeWorkHour')->name('employee-work-hour.create');
    Route::delete('/employee-work-hour/{data}', 'deleteEmployeeWorkHour')->name('employee-work-hour.delete');

    // Confirm Data
    Route::post('/employee-confirm-data/{data}', 'employeeConfirmData')->name('employee-confirm-data');
    Route::post('/employee-confirm-contract/{data}', 'employeeConfirmContract')->name('employee-confirm-contract');


    // CONTRACT
    Route::prefix('employee-contract')->group(function() {
        Route::name('employee-contract')->group(function() {
            Route::get('/{data1}/{data2}', 'getContractJobdesk');
            Route::post('/{data}', 'createContractJobdesk')->name('.store');
            Route::delete('/{data1}/{data2}', 'deleteContractJobdesk')->name('.delete');
        });
    });
});

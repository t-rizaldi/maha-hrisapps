<?php

use App\Http\Controllers\Employee\BranchController;
use App\Http\Controllers\Employee\DepartmentController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employeer\JobTitleController;
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

// EMPLOYEE
Route::controller(EmployeeController::class)->group(function() {
    Route::post('/register', 'register')->name('register');
});

// BRANCH
Route::controller(BranchController::class)->group(function() {
    Route::prefix('branch')->group(function() {
        Route::name('branch')->group(function() {
            Route::get('/', 'getAllParent');

            Route::middleware(VerifyToken::class)->group(function() {
                Route::get('/{data}/detail', 'detailBranch')->name('.detail');

                // PARENT BRANCH
                Route::post('/', 'createParent')->name('.create');
                Route::put('/{data}', 'updateParent')->name('.update');
                Route::delete('/{data}', 'deleteParent')->name('.delete');

                // PARENT BRANCH
                Route::prefix('children')->group(function() {
                    Route::name('children')->group(function() {
                        Route::get('/', 'getAllChildren');
                        Route::get('/parent/{data}', 'getAllChildrenByParentCode')->name('.parent');
                        Route::post('/', 'createChildren')->name('.create');
                        Route::put('/{data}', 'updateChildren')->name('.update');
                        Route::delete('/{data}', 'deleteChildren')->name('.delete');
                    });
                });

            });
        });
    });
});

// DEPARTMENT
Route::controller(DepartmentController::class)->group(function() {
    Route::prefix('department')->group(function() {
        Route::name('department')->group(function() {
            Route::get('/', 'index');

            Route::middleware(VerifyToken::class)->group(function() {
                Route::get('/{data}', 'detail')->name('.detail');
                Route::post('/', 'create')->name('.create');
                Route::put('/{data}', 'update')->name('.update');
                Route::delete('/{data}', 'delete')->name('.delete');
            });

        });
    });
});

// JOB TITLE
Route::controller(JobTitleController::class)->group(function() {
    Route::prefix('job-title')->group(function() {
        Route::name('job-title')->group(function() {
            Route::get('/department/{data}', 'getAllByDepartment')->name('.department');

            Route::middleware(VerifyToken::class)->group(function() {
                Route::get('/', 'index');
                Route::get('/{data}', 'detail')->name('.detail');
                Route::get('/type/{data}', 'getAllByType')->name('.type');
                Route::get('/type/{data}/{data1}', 'getAllByTypeRole')->name('.type.role');
                Route::post('/', 'create')->name('.create');
                Route::put('/{data}', 'update')->name('.update');
                Route::delete('/{data}', 'delete')->name('.delete');
            });

        });
    });
});

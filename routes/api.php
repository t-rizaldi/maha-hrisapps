<?php

use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Employee\BranchController;
use App\Http\Controllers\Employee\DepartmentController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\JobTitleController;
use App\Http\Controllers\Employee\SkillController;
use App\Http\Controllers\Employee\WorkHourController;
use App\Http\Controllers\Letter\CategoryController;
use App\Http\Controllers\Letter\LetterController;
use App\Http\Controllers\Region\RegionController;
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
                Route::get('/{data}', 'getUserById')->name('.user.detail');
                Route::post('/logout', 'logout')->name('.user');
            });
        });
    });
});

// EMPLOYEE
Route::prefix('employee')->group(function() {
    Route::name('employee')->group(function() {

        Route::controller(EmployeeController::class)->group(function() {
            Route::post('/register', 'register')->name('.register');
            Route::post('/login', 'login')->name('.login');
            Route::post('/refresh-token', 'refreshToken')->name('.refresh-token');

            Route::middleware(VerifyToken::class)->group(function() {
                Route::post('/verify-register', 'verifyRegister')->name('.verify-register');
                Route::put('/verify-data/{data}', 'verifyData')->name('.verify-data');
                Route::post('/logout', 'logout')->name('.logout');

                Route::get('/', 'index');
                Route::get('/{data}', 'getEmployeeById')->name('.detail');
                Route::post('/get-by-token', 'getEmployeeByToken')->name('.getByToken');

                // BIODATA
                Route::get('/employee-biodata/{data}', 'getEmployeeBiodata')->name('.biodata');
                Route::post('/employee-biodata', 'storeEmployeeBiodata')->name('.biodata.store');
                // EDUCATION
                Route::get('/employee-education/{data}', 'getEmployeeEducation')->name('.education');
                Route::post('/employee-education', 'storeEmployeeEducation')->name('.education.store');
                // FAMILY
                Route::get('/employee-family/{data}', 'getEmployeeFamily')->name('.family');
                Route::post('/employee-family', 'storeEmployeeFamily')->name('.family.store');
                // MARITAL
                Route::put('/employee-marital/{data}', 'updateEmployeeMarital')->name('.marital.update');
                // SIBLING
                Route::get('/employee-sibling/{data}', 'getAllSiblingByEmployeeId')->name('.sibling');
                Route::get('/employee-sibling/{data}/{data1}', 'getSiblingById')->name('.sibling.detail');
                Route::post('/employee-sibling', 'createEmployeeSibling')->name('.sibling.store');
                Route::put('/employee-sibling/{data}/{data1}', 'updateEmployeeSibling')->name('.sibling.update');
                Route::delete('/employee-sibling/{data}/{data1}', 'deleteEmployeeSibling')->name('.sibling.delete');
                // CHILDREN
                Route::get('/employee-children/{data}', 'getAllChildrenByEmployeeId')->name('.children');
                Route::get('/employee-children/{data}/{data1}', 'getChildrenById')->name('.children.detail');
                Route::post('/employee-children', 'createEmployeeChildren')->name('.children.store');
                Route::put('/employee-children/{data}/{data1}', 'updateEmployeeChildren')->name('.children.update');
                Route::delete('/employee-children/{data}/{data1}', 'deleteEmployeeChildren')->name('.children.delete');
                // DOCUMENT
                Route::get('/employee-document/{data}', 'getEmployeeDocument')->name('.document');
                Route::post('/employee-document', 'storeEmployeeDocument')->name('.document.store');
                Route::post('/employee-document/{data}', 'updateEmployeeDocument')->name('.document.update');
                Route::delete('/employee-document/{data}', 'deleteEmployeeDocument')->name('.document.delete');
                // SIGNATURE
                Route::get('/employee-signature/{data}', 'getEmployeeSignature')->name('.signature');
                Route::post('/employee-signature/{data}', 'createEmployeeSignature')->name('.signature.store');
                // SKILL
                Route::get('/employee-skill/{data}', 'getEmployeeSkill')->name('.skill');
                Route::post('/employee-skill/{data}', 'updateEmployeeSkill')->name('.skill.update');
                // WORK HOUR
                Route::get('/employee-work-hour/{data}', 'getEmployeeWorkHour')->name('.work-hour');
                Route::post('/employee-work-hour', 'createEmployeeWorkHour')->name('.work-hour.create');
                Route::delete('/employee-work-hour/{data}', 'deleteEmployeeWorkHour')->name('.work-hour.delete');

                // CONFIRM DATA
                Route::post('/confirm-data/{data}', 'employeeConfirmData')->name('.confirm-data');
                Route::post('/confirm-contract/{data}', 'employeeConfirmContract')->name('.confirm-contract');

                // CONTRACT
                Route::prefix('employee-contract')->group(function() {
                    Route::name('.employee-contract')->group(function() {
                        Route::get('/{data1}/{data2}', 'getContractJobdesk');
                        Route::post('/{data1}', 'createContractJobdesk')->name('.store');
                        Route::delete('/{data1}/{data2}', 'deleteContractJobdesk')->name('.delete');
                    });
                });
            });
        });
    });
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

// WORK HOUR
Route::controller(WorkHourController::class)->group(function() {
    Route::prefix('work-hour')->group(function() {
        Route::name('work-hour')->group(function() {
            Route::middleware(VerifyToken::class)->group(function() {
                Route::get('/', 'index');
                Route::get('/{data}', 'detail')->name('.detail');
                Route::post('/', 'store')->name('.store');
                Route::put('/{data}', 'update')->name('.update');
                Route::delete('/{data}', 'delete')->name('.delete');
            });
        });
    });
});

// LETTER
Route::prefix('letter')->group(function() {
    Route::name('letter')->group(function() {
        // category
        Route::controller(CategoryController::class)->group(function() {
            Route::prefix('category')->group(function() {
                Route::name('.category')->group(function() {
                    Route::middleware(VerifyToken::class)->group(function() {
                        Route::get('/', 'index');
                        Route::post('/', 'store')->name('.store');
                        Route::put('/{data}', 'update')->name('.update');
                        Route::delete('/{data}', 'delete')->name('.delete');
                        Route::get('/{data}', 'detail')->name('.detail');
                        Route::get('/type/{data}', 'getByType')->name('.type');
                    });
                });
            });
        });

        // letter list
        Route::controller(LetterController::class)->group(function() {
            Route::middleware(VerifyToken::class)->group(function() {
                Route::get('/', 'index');
                Route::post('/', 'store')->name('.store');
                Route::get('/new-number/company/{data}', 'getNewCompanyLetterNumber')->name('.company-new-number');
            });
        });
    });
});

// SKILL
Route::prefix('skill')->group(function() {
    Route::name('skill')->group(function() {
        Route::controller(SkillController::class)->group(function() {
            Route::get('/', 'index');
            Route::get('/{data}', 'getById')->name('.detail');

            Route::middleware(VerifyToken::class)->group(function() {
                Route::post('/', 'store')->name('.store');
                Route::put('/{data}', 'update')->name('.update');
                Route::delete('/{data}', 'delete')->name('.delete');
            });
        });
    });
});

// REGION
Route::prefix('region')->group(function() {
    Route::name('region')->group(function() {
        Route::controller(RegionController::class)->group(function() {
            Route::get('/all-province', 'getAllProvince')->name('.province');
            Route::get('/all-regency/{data}', 'getRegencyByProvinceId')->name('.regency');
            Route::get('/all-district/{data}', 'getDistrictByRegencyId')->name('.district');
            Route::get('/all-village/{data}', 'getVillageByDistrictId')->name('.village');
            Route::get('/province/{data}', 'getProvinceById')->name('.provinceById');
            Route::get('/regency/{data}', 'getRegencyById')->name('.regencyById');
            Route::get('/district/{data}', 'getDistrictById')->name('.districtById');
            Route::get('/village/{data}', 'getVillageById')->name('.villageById');
        });
    });
});

// ATTENDANCE
Route::prefix('attendance')->group(function() {
    Route::name('attendance')->group(function() {
        Route::controller(AttendanceController::class)->group(function() {
            Route::middleware(VerifyToken::class)->group(function() {
                // Attendance
                Route::get('/employee-history/{data}/{data1}/{data2}', 'employeeAttendanceHistory')->name('.employee-history');
                Route::post('/', 'storeAttendance')->name('.store');
                // Overtime
                Route::prefix('overtime')->group(function() {
                    Route::name('.overtime')->group(function() {
                        Route::post('/', 'storeOvertime')->name('.store');
                        Route::get('/{data}', 'getOvertimeByEmployeeId');
                        Route::put('/{data}/{data1}', 'updateOvertime')->name('.update');
                        Route::delete('/{data}/{data1}', 'deleteOvertime')->name('.delete');
                        // Overtime Photo
                        Route::prefix('photo')->group(function() {
                            Route::name('.photo')->group(function() {
                                Route::post('/{data}/{data1}', 'storeOvertimePhoto')->name('.store');
                            });
                        });
                    });
                });
            });
        });
    });
});

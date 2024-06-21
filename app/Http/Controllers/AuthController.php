<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\RefreshToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        try{
            // validation check
            $validator = Validator::make($request->all(), [
                'fullname'      => 'required|min:3|max:100',
                'email'         => 'required|email|unique:employees,email|ends_with:@mahasejahtera.com',
                'job_title_id'  => 'required',
                'department_id' => 'required',
                'branch_code'   => 'required',
                'password'      => 'required|min:6'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // BRANCH CHECK
            $branch = Branch::where('branch_code', $request->branch_code)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Branch not found'
                ], 200);
            }

            // DEPARTMENT CHECK
            $department = Department::find($request->department_id);

            if(empty($department)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found'
                ], 200);
            }

            // JOB TITLE CHECK
            $jobTitle = JobTitle::find($request->job_title_id);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job title not found'
                ], 200);
            }

            if($jobTitle->department_id != $request->department_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'job title does not match the department'
                ], 400);
            }

            // SET ROLE ID
            $jobTitle = JobTitle::find($request->job_title_id);
            $roleId = 1;

            if($jobTitle->role > 1) {
                $roleId = $jobTitle->role;
            }

            // CREATE DATA
            $data = [
                'fullname'      => Str::title($request->fullname),
                'email'         => $request->email,
                'job_title_id'  => $jobTitle->id,
                'department_id' => $request->department_id,
                'branch_code'   => $request->branch_code,
                'password'      => Hash::make($request->password),
                'role_id'       => $roleId,
                'status'        => 0
            ];

            $employee = Employee::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => [
                    'id'    => $employee->id
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // LOGIN
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'         => 'required|email',
                'password'      => 'required'
            ]);

            // cek validasi request
            if ($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // cek employee
            $employee = Employee::with(['jobTitle', 'department', 'branch', 'contract', 'biodata', 'education', 'family'])->where('email', $request->email)->first();

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'email or password wrong'
                ], 400);
            }

            // cek invalid password
            $isValidPassword = Hash::check($request->password, $employee->password);

            if(!$isValidPassword) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'email or password wrong'
                ], 400);
            }

            // status check
            if($employee->status == 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Unverified account'
                ], 400);
            }

            if($employee->status == 4) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Inactive account'
                ], 400);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // LOGOUT
    public function logout(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'   => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            $employeeId = $request->employee_id;
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Employee not found'
                ], 200);
            }

            RefreshToken::where('employee_id', $employeeId)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'Refresh token deleted'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // GET REFRESH TOKEN
    public function getRefreshToken($rt = null)
    {
        try {
            $refreshToken = RefreshToken::where('token', $rt)->first();

            if(empty($refreshToken)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Invalid token'
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $refreshToken
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // STORE REFRESH TOKEN
    public function refreshTokenStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'refresh_token'     => 'required'
            ]);

            // cek validasi
            if($validator->fails()) {
                return response()->json([
                    'status'        => 'error',
                    'code'          => 400,
                    'message'       => $validator->errors()
                ], 400);
            }

            // cek employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Employee not found'
                ], 200);
            }

            // create refresh token
            $createdRefreshToken = RefreshToken::create([
                'token'         => $request->refresh_token,
                'employee_id'   => $request->employee_id
            ]);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'id'        => $createdRefreshToken->id
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\RefreshToken;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
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
                // 'email'         => 'required|email|unique:employees,email|ends_with:@mahasejahtera.com',
                'email'         => 'required|email|unique:employees,email',
                'job_title_id'  => 'required',
                'department_id' => 'required',
                'branch_code'   => 'required',
                'password'      => 'required|min:6'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // BRANCH CHECK
            $branch = Branch::where('branch_code', $request->branch_code)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Branch not found',
                    'data'      => []
                ], 200);
            }

            // DEPARTMENT CHECK
            $department = Department::find($request->department_id);

            if(empty($department)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found',
                    'data'      => []
                ], 200);
            }

            // JOB TITLE CHECK
            $jobTitle = JobTitle::find($request->job_title_id);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job title not found',
                    'data'      => []
                ], 200);
            }

            if($jobTitle->department_id != $request->department_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'job title does not match the department',
                    'data'      => []
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
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // cek employee
            $employee = Employee::with(['jobTitle', 'department', 'branch', 'contract', 'biodata', 'education', 'family'])->where('email', $request->email)->first();

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'email or password wrong',
                    'data'      => []
                ], 403);
            }

            // cek invalid password
            $isValidPassword = Hash::check($request->password, $employee->password);

            if(!$isValidPassword) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'email or password wrong',
                    'data'      => []
                ], 403);
            }

            // status check
            if($employee->status == 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Unverified account',
                    'data'      => []
                ], 403);
            }

            if($employee->status == 4) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Inactive account',
                    'data'      => []
                ], 403);
            }

            if($employee->status == 1 && empty($employee->email_verified_at)) {
                return response()->json([
                    'status'    => 'pending',
                    'code'      => 403,
                    'message'   => 'Silahkan periksa Email lalu Klik Tautan di email untuk memverifikasi akun anda!',
                    'data'      => []
                ], 403);
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
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            $employeeId = $request->employee_id;
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Employee not found',
                    'data'      => []
                ], 200);
            }

            RefreshToken::where('employee_id', $employeeId)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'Refresh token deleted',
                'data'      => []
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // VERIFICATION MAIL
    public function verificationMail($employeeId, $hash)
    {
        $employee = Employee::findOrFail($employeeId);

        if (! hash_equals((string) $hash, sha1($employee->getEmailForVerification()))) {
            abort(403);
        }

        if ($employee->hasVerifiedEmail()) {
            // return response()->json([
            //     'status'    => 'success',
            //     'code'      => 200,
            //     'message'   => 'Akun telah diverifikasi'
            // ]);

            return "<h1>Akun berhasil diverifikasi</h1>";
        }

        if ($employee->markEmailAsVerified()) {
            event(new Verified($employee));
        }

        // return response()->json([
        //     'status'    => 'success',
        //     'code'      => 200,
        //     'message'   => 'Akun telah diverifikasi'
        // ]);

        return "<h1>Akun berhasil diverifikasi</h1>";
    }

    // GET REFRESH TOKEN
    public function getRefreshToken($rt = null)
    {
        try {
            $refreshToken = RefreshToken::where('token', $rt)->first();

            if(empty($refreshToken)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Invalid token'
                ], 403);
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

    public function changePassword(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'old_password'      => 'required',
                'password'          => 'required|confirmed|min:6'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // cek password lama sama atau tidak
            if(!Hash::check($request->old_password, $employee->password)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Kata sandi lama salah',
                    'data'      => []
                ], 403);
            }

            // kalau password baru sama dengan yang lama
            if(strcmp($request->old_password, $request->password) == 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'       => 'Kata sandi baru tidak boleh sama dengan yang lama',
                    'data'      => []
                ], 403);
            }

            // Update
            $data = [
                'password'  => Hash::make($request->password)
            ];

            Employee::where('id', $request->employee_id)->update($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => Employee::find($request->employee_id)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

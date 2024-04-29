<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobTitle;
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
                    'message'   => $validator->errors()
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
                'employee'  => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

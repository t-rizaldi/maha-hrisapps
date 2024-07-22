<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProjectAccountController extends Controller
{
    public function getProjectAccount(Request $request)
    {
        try {
            $accounts = Employee::with(['branch'])->where('role_id', 6);

            if($request->has('status')) {
                $status = $request->query('status');
                if(!empty($status) || $status == 0) $accounts->where('status', $status);
            }

            $accounts = $accounts->get();

            if(count($accounts) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $accounts
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getProjectAccountById($accountId)
    {
        try {
            $account = Employee::with(['branch'])->where('id', $accountId)->first();

            if(empty($account)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($account->role_id != 6) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $account
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeProjectAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fullname'      => 'required',
                'email'         => 'required|email|unique:employees,email',
                'branch_code'   => 'required',
                'password'      => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Branch Check
            $branch = Branch::where('branch_code', $request->branch_code)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Cabang tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Store
            $data = [
                'fullname'      => $request->fullname,
                'email'         => $request->email,
                'branch_code'   => $request->branch_code,
                'password'      => Hash::make($request->password),
                'role_id'       => 6,
                'status'        => 3
            ];

            $account = Employee::with(['branch'])->create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => Employee::with(['branch'])->where('id', $account->id)->first()
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateProjectAccount($accountId, Request $request)
    {
        try {
            // Get Project Account
            $account = Employee::with(['branch'])->where('id', $accountId)->first();

            if(empty($account)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($account->role_id != 6) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $rules = [
                'fullname'      => 'required',
                'email'         => 'required',
                'branch_code'   => 'required',
            ];

            if($account->email != $request->email) {
                $rules['email'] = 'required|email|unique:employees,email';
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Branch Check
            $branch = Branch::where('branch_code', $request->branch_code)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Cabang tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Update
            $account->fullname      = $request->fullname;
            $account->email         = $request->email;
            $account->branch_code   = $request->branch_code;
            $account->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $account
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteProjectAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_id' => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Project Account
            $account = Employee::find($request->account_id);

            if(empty($account)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($account->role_id != 6) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // DELETE
            Employee::where('id', $account->id)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
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

    public function changeStatusProjectAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_id'    => 'required',
                'status'        => 'required|in:3,4'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            $account = Employee::with(['branch'])->where('id', $request->account_id)->first();

            if(empty($account)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($account->role_id != 6) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Update
            $account->status = $request->status;
            $account->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $account
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function changePasswordProjectAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_id'    => 'required',
                'password'      => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            $account = Employee::with(['branch'])->where('id', $request->account_id)->first();

            if(empty($account)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($account->role_id != 6) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Akun proyek tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Update
            $account->password = Hash::make($request->password);
            $account->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $account
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

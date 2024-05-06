<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index()
    {
        try {
            // GET ALL DEPARTMENT
            $departments = Department::all();

            if(count($departments) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $departments
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function detail($departmentCode = null)
    {
        try {
            // GET DEPARTMENT
            $department = Department::where('department_code', $departmentCode)->first();

            if(empty($department)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found'
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $department
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'department_code'   => 'required|unique:departments,department_code|max:3',
                'department_name'   => 'required|unique:departments,department_name',
                'is_sub'            => 'required',
                'gm_num'            => 'numeric'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // CREATE
            $data = [
                'department_code'   => strtoupper($request->department_code),
                'department_name'   => $request->department_name,
                'is_sub'            => $request->is_sub,
                'gm_num'            => $request->gm_num,
            ];

            $department = Department::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $department
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function update($departmentCode = null, Request $request)
    {
        try {
            // GET DEPARTMENT
            $department = Department::where('department_code', $departmentCode)->first();

            if(empty($department)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found'
                ], 200);
            }

            // VALIDATION CHECK
            $rules = [
                'department_code'   => 'required|max:3',
                'department_name'   => 'required',
                'is_sub'            => 'required',
                'gm_num'            => 'numeric'
            ];

            if($request->department_code != $department->department_code) {
                $rules['department_code'] = 'required|unique:departments,department_code|max:3';
            }

            if($request->department_name != $department->department_name) {
                $rules['department_name'] = 'required|unique:departments,department_name';
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // UPDATE
            $data = [
                'department_code'   => strtoupper($request->department_code),
                'department_name'   => $request->department_name,
                'is_sub'            => $request->is_sub,
                'gm_num'            => $request->gm_num,
            ];

            Department::where('department_code', $departmentCode)->update($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function delete($departmentCode = null)
    {
        try {
            // GET DEPARTMENT
            $department = Department::where('department_code', $departmentCode)->first();

            if(empty($department)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found'
                ], 200);
            }

            // DELETE
            Department::where('department_code', $departmentCode)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK'
            ], 200);
        } catch(Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

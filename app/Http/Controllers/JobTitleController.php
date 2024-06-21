<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\JobTitle;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobTitleController extends Controller
{
    public function index()
    {
        try {
            $jobTitles = JobTitle::all();

            if(count($jobTitles) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job titles not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $jobTitles
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function detail($jobTitleId = null)
    {
        try {
            // GET JOB TITLE
            $jobTitle = JobTitle::find($jobTitleId);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job title not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $jobTitle
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getAllByDepartment($departmentId = null)
    {
        try {
            // GET DEPARTMENT
            $department = Department::find($departmentId);

            if(empty($department)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Department not found',
                    'data'      => []
                ], 200);
            }

            // GET JOB TITLES
            $jobTitles = JobTitle::where('department_id', $departmentId)->get();

            if(count($jobTitles) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job title not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $jobTitles
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getAllByType($type = null)
    {
        try {
            if($type != 0 && $type != 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'The input must be 0 or 1',
                    'data'      => []
                ], 400);
            }

            $jobTitles = JobTitle::where('is_daily', $type)->get();

            if(count($jobTitles) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job titles not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'mesage'    => 'OK',
                'data'      => $jobTitles
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getAllByTypeRole($type = null, $role = null)
    {
        try {
            if($type != 0 && $type != 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'The input must be 0 or 1',
                    'data'      => []
                ], 400);
            }

            $jobTitles = [];

            if($type == 0) {
                $jobTitles = JobTitle::where('is_daily', 0)
                                    ->where('role', $role)
                                    ->get();
            }

            if($type == 1) {
                $jobTitles = JobTitle::where('is_daily', 1)
                                    ->where('daily_level', $role)
                                    ->get();
            }

            if(count($jobTitles) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job titles not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'mesage'    => 'OK',
                'data'      => $jobTitles
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function create(Request $request)
    {
        try {
            // VALIDATION CHECK
            $rules = [
                'name'              => 'required',
                'is_daily'          => 'required',
            ];

            if(!$request->is_daily) {
                $rules['department_id'] = 'required';
                $rules['role'] = 'required';
            } else {
                $rules['daily_level'] = 'required';
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // CREATE
            $data = [
                'name'      => $request->name,
                'is_daily'  => $request->is_daily
            ];

            if(!$request->is_daily) {
                $data['department_id'] = $request->department_id;
                $data['role'] = $request->role;

                if($request->role == 3) {
                    $validator = Validator::make($request->all(), [
                        'gm_num'    => 'required'
                    ]);

                    if($validator->fails()) {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 400,
                            'message'   => $validator->errors()
                        ], 400);
                    }
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

            } else {
                $data['daily_level'] = $request->daily_level;
            }

            $jobTitle = JobTitle::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $jobTitle
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function update($jobTitleId = null, Request $request)
    {
        try {
            // GET JOB TITLE
            $jobTitle = JobTitle::find($jobTitleId);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job title not found'
                ], 200);
            }

            // VALIDATION CHECK
            $rules = [
                'name'              => 'required',
                'is_daily'          => 'required',
            ];

            if(!$request->is_daily) {
                $rules['department_id'] = 'required';
                $rules['role'] = 'required';
            } else {
                $rules['daily_level'] = 'required';
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
                'name'      => $request->name,
                'is_daily'  => $request->is_daily
            ];

            if(!$request->is_daily) {
                $data['department_id'] = $request->department_id;
                $data['role'] = $request->role;

                // DEPARTMENT CHECK
                $department = Department::find($request->department_id);

                if(empty($department)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Department not found'
                    ], 200);
                }

            } else {
                $data['daily_level'] = $request->daily_level;
            }

            JobTitle::where('id', $jobTitleId)->update($data);

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

    public function delete($jobTitleId = null)
    {
        try {
            // GET JOB TITLE
            $jobTitle = JobTitle::find($jobTitleId);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Job title not found'
                ], 200);
            }

            // DELETE
            JobTitle::where('id', $jobTitleId)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK'
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

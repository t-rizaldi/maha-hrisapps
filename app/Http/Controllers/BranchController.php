<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function detail($branchCode = null)
    {
        try {
            // GET BRANCH
            $branch = Branch::where('branch_code', $branchCode)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Branch not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $branch
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // PARENT BRANCH
    public function index()
    {
        try {
            // GET ALL BRANCH
            $branches = Branch::where('is_sub', 0)->get();

            if(count($branches) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Branch not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'data'      => $branches
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeParentBranch(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'branch_code'       => 'required|unique:branches,branch_code',
                'branch_name'       => 'required|unique:branches,branch_name',
                'branch_location'   => 'required',
                'branch_radius'     => 'required',
                'is_project'        => 'required',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // CREATE
            $data = [
                'branch_code'       => strtoupper($request->branch_code),
                'branch_name'       => $request->branch_name,
                'branch_location'   => $request->branch_location,
                'branch_radius'     => $request->branch_radius,
                'is_project'        => $request->is_project,
                'is_sub'            => false,
                'is_active'         => true
            ];

            $branch = Branch::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $branch
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateParentBranch($branchCode = null, Request $request)
    {
        try {
            // GET BRANCH
            $branch = Branch::where('branch_code', $branchCode)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Branch not found',
                    'data'      => []
                ], 200);
            }

            $rules = [
                'branch_code'       => 'required',
                'branch_name'       => 'required',
                'branch_location'   => 'required',
                'branch_radius'     => 'required',
                'is_project'        => 'required',
                'is_active'         => 'required',
            ];

            if($request->branch_code != $branch->branch_code) {
                $rules['branch_code'] = 'required|unique:branches,branch_code';
            }

            if($request->branch_name != $branch->branch_name) {
                $rules['branch_name'] = 'required|unique:branches,branch_name';
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

            // UPDATE
            $data = [
                'branch_code'       => strtoupper($request->branch_code),
                'branch_name'       => $request->branch_name,
                'branch_location'   => $request->branch_location,
                'branch_radius'     => $request->branch_radius,
                'is_project'        => $request->is_project,
                'is_active'         => $request->is_active,
            ];

            Branch::where('branch_code', $branchCode)->update($data);

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

    public function deleteParentBranch($branchCode = null)
    {
        try {
            // GET BRANCH
            $branch = Branch::where('branch_code', $branchCode)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Branch not found',
                    'data'      => []
                ], 200);
            }

            // DELETE CHILD
            Branch::where('branch_parent_code', $branchCode)->delete();
            // DELETE PARENT
            Branch::where('branch_code', $branchCode)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => []
            ], 200);
        } catch(Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // CHILDREN BRANCH
    public function getAllChildrenBranch()
    {
        try {
            // GET ALL BRANCH
            $branches = Branch::where('is_sub', 1)->get();

            if(count($branches) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Children Branch not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $branches
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getAllChildrenByParentCode($parentCode = null)
    {
        try {
            // GET ALL BRANCH
            $branches = Branch::where('is_sub', 1)
                            ->where('branch_parent_code', $parentCode)
                            ->get();

            if(count($branches) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Children Branch not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $branches
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeChildrenBranch(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'branch_code'           => 'required|unique:branches,branch_code',
                'branch_name'           => 'required|unique:branches,branch_name',
                'branch_location'       => 'required',
                'branch_radius'         => 'required',
                'is_project'            => 'required',
                'branch_parent_code'    => 'required',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // PARENT CHECK
            $parentBranch = Branch::where('branch_code', $request->branch_parent_code)
                                ->where('is_sub', 0)
                                ->first();

            if(empty($parentBranch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Parent branch not found',
                    'data'      => []
                ], 200);
            }

            // CREATE
            $data = [
                'branch_code'           => strtoupper($request->branch_code),
                'branch_name'           => $request->branch_name,
                'branch_location'       => $request->branch_location,
                'branch_radius'         => $request->branch_radius,
                'is_project'            => $request->is_project,
                'is_sub'                => true,
                'branch_parent_code'    => strtoupper($request->branch_parent_code),
                'is_active'             => true
            ];

            $branch = Branch::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $branch
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateChildrenBranch($branchCode = null, Request $request)
    {
        try {
            // GET BRANCH
            $branch = Branch::where('branch_code', $branchCode)
                            ->where('is_sub', 1)
                            ->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Child Branch not found'
                ], 200);
            }

            $rules = [
                'branch_code'           => 'required',
                'branch_name'           => 'required',
                'branch_location'       => 'required',
                'branch_radius'         => 'required',
                'is_project'            => 'required',
                'branch_parent_code'    => 'required',
                'is_active'             => 'required',
            ];

            if($request->branch_code != $branch->branch_code) {
                $rules['branch_code'] = 'required|unique:branches,branch_code';
            }

            if($request->branch_name != $branch->branch_name) {
                $rules['branch_name'] = 'required|unique:branches,branch_name';
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
                'branch_code'           => strtoupper($request->branch_code),
                'branch_name'           => $request->branch_name,
                'branch_location'       => $request->branch_location,
                'branch_radius'         => $request->branch_radius,
                'is_project'            => $request->is_project,
                'branch_parent_code'    => $request->branch_parent_code,
                'is_active'             => $request->is_active,
            ];

            Branch::where('branch_code', $branchCode)->update($data);

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

    public function deleteChildrenBranch($branchCode = null)
    {
        try {
            // GET BRANCH
            $branch = Branch::where('branch_code', $branchCode)
                            ->where('is_sub', 1)
                            ->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Child branch not found'
                ], 200);
            }

            // DELETE
            Branch::where('branch_code', $branchCode)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => []
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

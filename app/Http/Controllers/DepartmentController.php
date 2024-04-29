<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function store(Request $request)
    {
        // Validation Check
        $validator = Validator::make($request->all(), [
            'department_code'   => 'required|max:3',
            'department_name'   => 'required',
            'is_sub'            => 'required',
            'gm_num'            => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([], 400);
        }
    }
}

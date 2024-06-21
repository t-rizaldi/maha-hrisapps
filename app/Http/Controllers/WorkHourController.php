<?php

namespace App\Http\Controllers;

use App\Models\WorkHour;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkHourController extends Controller
{

    public function index()
    {
        try {
            $workHours = WorkHour::all();

            if(count($workHours) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $workHours
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function detail($code)
    {
        try {
            $workHour = WorkHour::where('work_hour_code', $code)->first();

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $workHour
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
            $validator = Validator::make($request->all(), [
                'work_hour_code'    => 'required|unique:work_hours,work_hour_code',
                'work_hour_name'    => 'required',
                'start_entry_hour'  => 'required|date_format:H:i:s',
                'entry_hour'        => 'required|date_format:H:i:s',
                'end_entry_hour'    => 'required|date_format:H:i:s',
                'home_hour'         => 'required|date_format:H:i:s',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // STORE
            $data = [
                'work_hour_code'    => $request->work_hour_code,
                'work_hour_name'    => $request->work_hour_name,
                'start_entry_hour'  => $request->start_entry_hour,
                'entry_hour'        => $request->entry_hour,
                'end_entry_hour'    => $request->end_entry_hour,
                'home_hour'         => $request->home_hour,
            ];

            $workHour = WorkHour::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $workHour
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function update($code, Request $request)
    {
        try {
            $workHour = WorkHour::where('work_hour_code', $code)->first();

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $rules = [
                'work_hour_code'    => 'required',
                'work_hour_name'    => 'required',
                'start_entry_hour'  => 'required|date_format:H:i:s',
                'entry_hour'        => 'required|date_format:H:i:s',
                'end_entry_hour'    => 'required|date_format:H:i:s',
                'home_hour'         => 'required|date_format:H:i:s',
            ];

            if($request->work_hour_code != $workHour->work_hour_code) $rules['work_hour_code'] = 'required|unique:work_hours,work_hour_code';

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
                'work_hour_code'    => $request->work_hour_code,
                'work_hour_name'    => $request->work_hour_name,
                'start_entry_hour'  => $request->start_entry_hour,
                'entry_hour'        => $request->entry_hour,
                'end_entry_hour'    => $request->end_entry_hour,
                'home_hour'         => $request->home_hour,
            ];

            WorkHour::where('work_hour_code', $code)->update($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => WorkHour::where('work_hour_code', $request->work_hour_code)->first()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function delete($code)
    {
        try {
            $workHour = WorkHour::where('work_hour_code', $code)->first();

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            WorkHour::where('work_hour_code', $code)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
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

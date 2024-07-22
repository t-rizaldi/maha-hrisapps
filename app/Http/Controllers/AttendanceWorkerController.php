<?php

namespace App\Http\Controllers;

use App\Models\AttendanceWorker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceWorkerController extends BaseController
{
    /*========Photo Attendance==========*/
    // Store
    public function storeAttendance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'worker_id'             => 'required',
                'attendance_date'       => 'required|date',
                'attendance_time'       => 'required|date_format:H:i:s',
                'attendance_location'   => 'required',
                'attendance_photo'      => 'required|image',
                'attendance_branch'     => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // get request
            $workerId = $request->worker_id;
            $attendanceDate = $request->attendance_date;
            $attendanceTime = $request->attendance_time;
            $attendanceLocation = $request->attendance_location;
            $attendancePhoto = $request->file('attendance_photo');

            // get attendance lat & long
            [$attendanceLattitude, $attendanceLongitude] = explode(',', $attendanceLocation);
            // In Zone Check
            $inZone = $this->checkInZoneAttendance($attendanceLattitude, $attendanceLongitude);

            // Get Employee
            $worker = $this->getWorker($workerId);

            if($worker['status'] == 'error') {
                return response()->json($worker, 200);
            }

            $worker = $worker['data'];

            // Get Branch
            if(empty($worker['branch_code'])) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kode cabang pekerja kosong'
                ], 200);
            }

            $branch = $this->getBranch($request->attendance_branch);

            if($branch['status'] == 'error') {
                return response()->json($branch, 200);
            }

            $branch = $branch['data'];

            // check sub or no
            if($branch['is_sub'] == 1) {
                if(strtoupper($branch['branch_parent_code']) != strtoupper($worker['branch_code'])) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Subcabang absen tidak sesuai dengan cabang pekerja'
                    ], 400);
                }
            } else {
                if(strtoupper($branch['branch_code']) != strtoupper($worker['branch_code'])) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Cabang absen tidak sesuai dengan cabang pekerja'
                    ], 400);
                }
            }

            // Branch location
            [$branchLattitude, $branchLongitude] = explode(',', $branch['branch_location']);

            // Get Work Hour
            $dayName = strtolower(date('l', strtotime($attendanceDate)));
            $workHour = $this->getWorkerWorkHour($workerId);

            if($workHour['status'] == 'error') {
                return response()->json($workHour, 200);
            }

            $workHour = $workHour['data'][$dayName . '_code'];

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Tidak ada jam kerja'
                ], 200);
            }

            $entryHour = $workHour['entry_hour'];
            $homeHour = $workHour['home_hour'];

            // calculate distance
            $distance = round($this->calculateDistance($branchLattitude, $branchLongitude, $attendanceLattitude, $attendanceLongitude));

            // attandance check
            $attendanceCheck = AttendanceWorker::where('attendance_date', $attendanceDate)
                                        ->where('worker_id', $workerId)
                                        ->count();

            if ($attendanceCheck > 0) {
                $attStatus = "out";
            } else {
                $attStatus = "in";
            }

            if($distance > $branch['branch_radius']) {

                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => "Maaf Anda Berada Diluar Radius, Jarak Anda " . number_format($distance, 0, ',', '.') . " meter dari lokasi yang ditentukan",
                    'data'      => []
                ], 403);

            } else {

                if($attendanceCheck < 1) {
                    if($attendanceTime < $workHour['start_entry_hour']) {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 400,
                            'message'   => 'Maaf, belum waktunya melakukan presensi'
                        ], 200);
                    }

                    if($attendanceTime > $workHour['end_entry_hour']) {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 400,
                            'message'   => 'Maaf, waktu presensi sudah habis'
                        ], 200);
                    }

                    $data = [
                        'worker_id'         => $workerId,
                        'attendance_date'   => $attendanceDate,
                        'entry_schedule'    => $entryHour,
                        'home_schedule'     => $homeHour,
                        'clock_in'          => $attendanceTime,
                        'location_in'       => $attendanceLocation,
                        'work_hour_code'    => $workHour['work_hour_code'],
                        'clock_in_type'     => 1,
                        'clock_in_zone'     => $inZone,
                        'branch_attendance' => $worker['branch_code']
                    ];

                    if($attendanceTime > $entryHour) {
                        $data['is_late'] = 1;
                    } else {
                        $data['is_late'] = 0;
                    }

                    $photoName = $attendanceDate . "_$attStatus." . $attendancePhoto->getClientOriginalExtension();
                    $path = "attendance/worker/$workerId/$photoName";

                    // Hapus file lama jika ada
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }

                    $pathUpload = $attendancePhoto->storeAs("attendance/worker/$workerId", $photoName);
                    $data['photo_in'] = $pathUpload;

                    $attendance = AttendanceWorker::create($data);

                    return response()->json([
                        'status'    => 'success',
                        'code'      => 201,
                        'message'   => 'OK',
                        'data'      => $attendance
                    ], 201);

                } else {
                    if($attendanceTime < $workHour['home_hour']) {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 400,
                            'message'   => 'Maaf, belum waktunya pulang'
                        ], 200);
                    }

                    $data = [
                        'clock_out'         => $attendanceTime,
                        'location_out'      => $attendanceLocation,
                        'clock_out_type'    => 1,
                        'clock_out_zone'    => $inZone,
                    ];

                    $photoName = $attendanceDate . "_$attStatus." . $attendancePhoto->getClientOriginalExtension();
                    $path = "attendance/worker/$workerId/$photoName";

                    // Hapus file lama jika ada
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }

                    $pathUpload = $attendancePhoto->storeAs("attendance/worker/$workerId", $photoName);
                    $data['photo_out'] = $pathUpload;

                    AttendanceWorker::where('attendance_date', $attendanceDate)
                                    ->where('worker_id', $workerId)
                                    ->update($data);

                    $attendance = AttendanceWorker::where('attendance_date', $attendanceDate)
                                    ->where('worker_id', $workerId)
                                    ->first();

                    return response()->json([
                        'status'    => 'success',
                        'code'      => 200,
                        'message'   => 'OK',
                        'data'      => $attendance
                    ], 200);
                }
            }

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeOvertime(Request $request)
    {
        try {

            $messages = [
                'required'                  => ':attribute wajib diisi !',
                'status.in'                 => 'status harus start atau finish !',
                'date'                      => ':attribute harus format Y-m-d (2022-01-01) !',
                'overtime_time.date_format' => ':attribute harus format H:i (18:23) !',
                'overtime_photo.image'      => ':attribute harus file gambar !'
            ];

            $validator = Validator::make($request->all(), [
                'worker_id'             => 'required',
                'status'                => 'required|in:start,finish',
                'overtime_date'         => 'required|date',
                'overtime_time'         => 'required|date_format:H:i',
                'overtime_location'     => 'required',
                'overtime_photo'        => 'required|image'
            ], $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            $workerData = $this->getWorker($request->worker_id);

            $attendanceWorker = AttendanceWorker::where('worker_id', $request->worker_id)
                ->where('attendance_date', $request->overtime_date)
                ->first();

            if ($workerData['status'] == 'error') {
                return response()->json($workerData, 200);
            }

            if (empty($attendanceWorker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 404,
                    'message'   => 'Data absensi tidak ditemukan !',
                    'data'      => []
                ], 404);
            }

            $status = $request->status;

            if ($status == 'start') {

                if (empty($attendanceWorker->clock_out)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Absen pulang terlebih dahulu !',
                        'data'      => []
                    ], 400);
                }

                if (!empty($attendanceWorker->overtime_start)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Lembur mulai sudah ada !',
                        'data'      => []
                    ], 400);
                }
            }

            if ($status == 'finish') {

                if (empty($attendanceWorker->overtime_start)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Lembur mulai belum ada !',
                        'data'      => []
                    ], 400);
                }

                if (!empty($attendanceWorker->overtime_finish)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Lembur selesai sudah ada !',
                        'data'      => []
                    ], 400);
                }
            }

            $data = [
                "overtime_$status"                  => $request->overtime_time,
                "overtime_$status" . "_location"    => $request->overtime_location,
            ];

            $photoName = $request->overtime_date . "_$status" . "_$request->overtime_time" . "." .  $request->file('overtime_photo')->getClientOriginalExtension();
            $pathUpload = $request->file('overtime_photo')->storeAs("overtime/worker/$request->worker_id", $photoName);
            $data["overtime_$status" . "_photo"] = $pathUpload;

            $attendanceWorker->update($data);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data' => $attendanceWorker
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

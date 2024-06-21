<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\OvertimePhoto;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends BaseController
{

    /*=================================
                ATTENDANCE
    =================================*/

    /*========General==========*/
    // API Google Maps
    /*
        public function checkInZoneAttendance($lat, $long)
        {
            try {
                $responseData = $this->client->get("$this->apiGeocodingMaps", [
                        'query'     => [
                        'latlng'    => "$lat,$long",
                        'language'  => 'id',
                        'key'       => $this->mapsApiKey
                    ]
                ]);

                $statusCode = $responseData->getStatusCode();
                $body = $responseData->getBody()->getContents();

                $response = json_decode($body, true);
                $areaInZone = ["kota medan", "kabupaten deli serdang"];

                if (isset($response['results'][0])) {
                    foreach ($response['results'][0]['address_components'] as $component) {
                        if (in_array('administrative_area_level_2', $component['types'])) {
                            $area = strtolower($component['long_name']);
                            $inZoneCheck = in_array($area, $areaInZone);

                            return response()->json([
                                'status'    => 'success',
                                'code'      => 200,
                                'message'   => 'OK',
                                'data'      => [
                                    'inZone'    => $inZoneCheck
                                ]
                            ], 200);
                        }
                    }
                }

            } catch (ClientException $e) {
                $responseData = $e->getResponse();
                $statusCode = $responseData->getStatusCode();
                $body = $responseData->getBody()->getContents();
                $response = json_decode($body);

                return response()->json([$response], $statusCode);
            }
        }
    */

    // API Mapbox
    public function checkInZoneAttendance($lat, $long)
    {
        try {
            $responseData = $this->client->get("$this->apiGeocodingMaps/reverse", [
                'query'     => [
                    'longitude'     => "$long",
                    'latitude'      => "$lat",
                    'language'      => 'id',
                    'access_token'  => $this->mapsApiKey
                ]
            ]);

            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);
            $areaInZone = ["medan", "deli serdang"];

            if (isset($response['features'][0])) {
                $area = strtolower($response['features'][0]['properties']['context']['place']['name']);
                $inZoneCheck = in_array($area, $areaInZone);
                return $inZoneCheck;
            }

            return false;

        } catch (ClientException $e) {
            return false;
        }
    }

    // distance
    public function calculateDistance($branchLat, $branchLong, $attLat, $attLong)
    {
        $theta = $branchLong - $attLong;
        $miles = (sin(deg2rad($branchLat)) * sin(deg2rad($attLat))) + (cos(deg2rad($branchLat)) * cos(deg2rad($attLat)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return $meters;
    }

    public function employeeAttendanceHistory($employeeId, $startDate, $endDate)
    {
        try {
            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];

            // Get attendance history
            $attendances = Attendance::where('employee_id', $employeeId)
                                    ->whereBetween('attendance_date', [$startDate, $endDate])
                                    ->get();


            if(count($attendances) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Data absensi tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $attendances
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    /*========Photo Attendance==========*/
    // Store
    public function storeAttendance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'           => 'required',
                'attendance_date'       => 'required|date',
                'attendance_time'       => 'required|date_format:H:i:s',
                'attendance_location'   => 'required',
                'attendance_photo'      => 'required|image'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // get request
            $employeeId = $request->employee_id;
            $attendanceDate = $request->attendance_date;
            $attendanceTime = $request->attendance_time;
            $attendanceLocation = $request->attendance_location;
            $attendancePhoto = $request->file('attendance_photo');

            // get attendance lat & long
            [$attendanceLattitude, $attendanceLongitude] = explode(',', $attendanceLocation);
            // In Zone Check
            $inZone = $this->checkInZoneAttendance($attendanceLattitude, $attendanceLongitude);

            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];

            // Get Branch
            if(empty($employee['branch_code'])) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kode cabang karyawan kosong'
                ], 200);
            }

            $branch = $this->getBranch($employee['branch_code']);

            if($branch['status'] == 'error') {
                return response()->json($branch, 200);
            }

            $branch = $branch['data'];
            // Branch location
            [$branchLattitude, $branchLongitude] = explode(',', $branch['branch_location']);

            // Get Work Hour
            $dayName = strtolower(date('l'));
            $workHour = $this->getEmployeeWorkHour($employeeId);

            if($workHour['status'] == 'error') {
                return response()->json($workHour, 200);
            }

            $workHour = $workHour['data'][$dayName . '_code'];

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => '204',
                    'message'   => 'Tidak ada jam kerja'
                ], 200);
            }

            $entryHour = $workHour['entry_hour'];
            $homeHour = $workHour['home_hour'];

            // calculate distance
            $distance = round($this->calculateDistance($branchLattitude, $branchLongitude, $attendanceLattitude, $attendanceLongitude));

            // attandance check
            $attendanceCheck = Attendance::where('attendance_date', $attendanceDate)
                                        ->where('employee_id', $employeeId)
                                        ->count();

            if ($attendanceCheck > 0) {
                $attStatus = "out";
            } else {
                $attStatus = "in";
            }

            $flexibleAtt = $employee['is_flexible_absent'];

            if($distance > $branch['branch_radius']) {

                if($flexibleAtt == 1) {

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
                            'employee_id'       => $employeeId,
                            'attendance_date'   => $attendanceDate,
                            'entry_schedule'    => $entryHour,
                            'home_schedule'     => $homeHour,
                            'clock_in'          => $attendanceTime,
                            'location_in'       => $attendanceLocation,
                            'work_hour_code'    => $workHour['work_hour_code'],
                            'clock_in_type'     => 1,
                            'clock_in_status'   => 0,
                            'clock_in_zone'     => $inZone,
                            'branch_attendance' => $employee['branch_code']
                        ];

                        if($attendanceTime > $entryHour) {
                            $data['is_late'] = 1;
                        } else {
                            $data['is_late'] = 0;
                        }

                        $photoName = $attendanceDate . "_$attStatus." . $attendancePhoto->getClientOriginalExtension();
                        $path = "attendance/employee/$employeeId/$photoName";

                        // Hapus file lama jika ada
                        if (Storage::exists($path)) {
                            Storage::delete($path);
                        }

                        $pathUpload = $attendancePhoto->storeAs("attendance/employee/$employeeId", $photoName);
                        $data['photo_in'] = $pathUpload;

                        $attendance = Attendance::create($data);

                        return response()->json([
                            'status'    => 'success',
                            'code'      => 201,
                            'message'   => 'Anda absen diluar radius dan memerlukan persetujuan admin',
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
                            'clock_out_status'  => 0,
                            'clock_out_zone'    => $inZone,
                        ];

                        $photoName = $attendanceDate . "_$attStatus." . $attendancePhoto->getClientOriginalExtension();
                        $path = "attendance/employee/$employeeId/$photoName";

                        // Hapus file lama jika ada
                        if (Storage::exists($path)) {
                            Storage::delete($path);
                        }

                        $pathUpload = $attendancePhoto->storeAs("attendance/employee/$employeeId", $photoName);
                        $data['photo_out'] = $pathUpload;

                        Attendance::where('attendance_date', $attendanceDate)
                                        ->where('employee_id', $employeeId)
                                        ->update($data);

                        $attendance = Attendance::where('attendance_date', $attendanceDate)
                                        ->where('employee_id', $employeeId)
                                        ->first();

                        return response()->json([
                            'status'    => 'success',
                            'code'      => 200,
                            'message'   => 'Absen pulang diluar radius dan memerlukan persetujuan admin',
                            'data'      => $attendance
                        ], 200);
                    }

                } else {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => "Maaf Anda Berada Diluar Radius, Jarak Anda " . number_format($distance, 0, ',', '.') . " meter dari lokasi yang ditentukan"
                    ], 200);
                }

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
                        'employee_id'       => $employeeId,
                        'attendance_date'   => $attendanceDate,
                        'entry_schedule'    => $entryHour,
                        'home_schedule'     => $homeHour,
                        'clock_in'          => $attendanceTime,
                        'location_in'       => $attendanceLocation,
                        'work_hour_code'    => $workHour['work_hour_code'],
                        'clock_in_type'     => 1,
                        'clock_in_zone'     => $inZone,
                        'branch_attendance' => $employee['branch_code']
                    ];

                    if($attendanceTime > $entryHour) {
                        $data['is_late'] = 1;
                    } else {
                        $data['is_late'] = 0;
                    }

                    // cek karyawan inti di proyek, kalau iya uang makan include 3
                    if ($employee['employee_status'] != 'daily' && $employee['employee_status'] != 'project' && $branch['is_project'] == 1) $data['meal_num'] = 3;

                    $photoName = $attendanceDate . "_$attStatus." . $attendancePhoto->getClientOriginalExtension();
                    $path = "attendance/employee/$employeeId/$photoName";

                    // Hapus file lama jika ada
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }

                    $pathUpload = $attendancePhoto->storeAs("attendance/employee/$employeeId", $photoName);
                    $data['photo_in'] = $pathUpload;

                    $attendance = Attendance::create($data);

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
                    $path = "attendance/employee/$employeeId/$photoName";

                    // Hapus file lama jika ada
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }

                    $pathUpload = $attendancePhoto->storeAs("attendance/employee/$employeeId", $photoName);
                    $data['photo_out'] = $pathUpload;

                    Attendance::where('attendance_date', $attendanceDate)
                                    ->where('employee_id', $employeeId)
                                    ->update($data);

                    $attendance = Attendance::where('attendance_date', $attendanceDate)
                                    ->where('employee_id', $employeeId)
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


    //=========================================

    /*=================================
                OVERTIME
    =================================*/

    public function storeOvertime(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'overtime_date'     => 'required|date',
                'subject'           => 'required',
                'description'       => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // check date
            $overtimeCheck = Overtime::where('employee_id', $request->employee_id)
                                    ->where('overtime_date', $request->overtime_date)
                                    ->count();

            if($overtimeCheck > 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Tidak bisa menginput lembur pada tanggal yang sama',
                    'data'      => []
                ], 400);
            }

            // Get Employee
            $employee = $this->getEmployee($request->employee_id);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];

            // Get Branch
            if(empty($employee['branch_code'])) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kode cabang karyawan kosong',
                    'data'      => []
                ], 200);
            }

            $branch = $this->getBranch($employee['branch_code']);

            if($branch['status'] == 'error') {
                return response()->json($branch, 200);
            }

            $branch = $branch['data'];

            // Attendance Check
            $attendance = Attendance::where('employee_id', $request->employee_id)
                                    ->where('attendance_date', $request->overtime_date)
                                    ->first();

            if(empty($attendance)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Tidak melakukan absensi hari ini',
                    'data'      => []
                ], 403);
            }

            if(empty($attendance->clock_out)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Belum absen pulang',
                    'data'      => []
                ], 403);
            }

            $data = [
                'employee_id'       => $request->employee_id,
                'overtime_date'     => $request->overtime_date,
                'subject'           => $request->subject,
                'description'       => $request->description,
                'overtime_branch'   => $branch['branch_code'],
                'approved_status'   => 11
            ];

            $overtime = Overtime::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $overtime
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getOvertimeByEmployeeId($employeeId)
    {
        try {
            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $overtimes = Overtime::where('employee_id', $employeeId)->get();

            if(count($overtimes) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Lembur tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $overtimes
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateOvertime($employeeId, $overtimeDate, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_time'    => 'date_format:H:i',
                'end_time'      => 'date_format:H:i',
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
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            // Get Overtime
            $overtime = Overtime::where('employee_id', $employeeId)
                                ->where('overtime_date', $overtimeDate)
                                ->first();

            if(empty($overtime)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Lembur tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $data = [];

            if(!empty($request->start_time)) $data['start_time'] = $request->start_time;
            if(!empty($request->end_time)) $data['end_time'] = $request->end_time;

            if(!empty($data)) {
                Overtime::where('employee_id', $employeeId)
                        ->where('overtime_date', $overtimeDate)
                        ->update($data);

                $overtimeUpdate = Overtime::where('employee_id', $employeeId)
                                            ->where('overtime_date', $overtimeDate)
                                            ->first();

                return response()->json([
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'OK',
                    'data'      => $overtimeUpdate
                ], 200);
            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Tidak ada request yang dikirim',
                    'data'      => []
                ], 200);
            }

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteOvertime($employeeId, $overtimeDate)
    {
        try {
            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            // Get Overtime
            $overtime = Overtime::where('employee_id', $employeeId)
                                ->where('overtime_date', $overtimeDate)
                                ->first();

            if(empty($overtime)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Lembur tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Delete
            Overtime::where('employee_id', $employeeId)
                ->where('overtime_date', $overtimeDate)
                ->delete();

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

    //======Overtime Photo========
    public function storeOvertimePhoto($employeeId, $overtimeId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'photo'         => 'required|image',
                'time'          => 'required|date_format:H:i',
                'status'        => 'required|in:1,2,3',
                'location'      => 'required'
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
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];

            // Get Overtime
            $overtime = Overtime::find($overtimeId);

            if(empty($overtime)) {
                return response()->json([
                    'ststus'    => 'error',
                    'code'      => 204,
                    'message'   => 'Lembur tidak ditemukan'
                ], 200);
            }

            // Cek kesesuaian
            if($employee['id'] != $overtime->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Lembur tidak sesuai dengan karyawan',
                    'data'      => []
                ], 200);
            }

            // store
            $status = $request->status;
            $overtimePhoto = $request->file('photo');

            $overtimeStatusLabel = '';
            if ($status == 1) $overtimeStatusLabel = 'mulai';
            if ($status == 2) $overtimeStatusLabel = 'berlangsung';
            if ($status == 3) $overtimeStatusLabel = 'selesai';

            $photoCheck = OvertimePhoto::where('overtime_id', $overtimeId)
                                    ->where('status', $status)
                                    ->get();

            if ($status == 1 || $status == 3) {
                // photo finish check
                if($status == 3) {
                    $photoCount = OvertimePhoto::where('overtime_id', $overtimeId)
                                            ->where('status', 2)
                                            ->count();

                    if($photoCount < 3) {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 403,
                            'message'   => 'Foto berlangsung minimal 3 sebelum melakukan foto selesai',
                            'data'      => []
                        ], 403);
                    }
                }

                if (count($photoCheck) > 0) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => "Foto $overtimeStatusLabel sudah dilakukan sebelumnya!",
                        'data'      => []
                    ], 400);

                } else {

                    $photoName = $overtime->overtime_date . "_$overtimeStatusLabel" . "_" . (count($photoCheck) + 1) . '.' . $overtimePhoto->getClientOriginalExtension();
                    $path = "overtime/employee/$employeeId/$photoName";

                    // Hapus file lama jika ada
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }

                    $pathUpload = $overtimePhoto->storeAs("overtime/employee/$employeeId", $photoName);

                    $data = [
                        'overtime_id'       => $overtimeId,
                        'photo'             => $pathUpload,
                        'status'            => $status,
                        'location'          => $request->location
                    ];

                    // create
                    $ovtPhoto = OvertimePhoto::create($data);

                    //Update Overtime Start & Finish
                    $photoTime = $request->time;

                    if ($status == 1) {
                        $overtime->start_time = $photoTime;
                        $overtime->save();
                    }

                    if ($status == 3) {
                        $overtime->end_time = $photoTime;
                        $overtime->save();
                    }

                    return response()->json([
                        'status'    => 'success',
                        'code'      => 201,
                        'message'   => 'OK',
                        'data'      => $ovtPhoto
                    ], 201);

                }

            } else {
                // photo progress check
                $photoCount = OvertimePhoto::where('overtime_id', $overtimeId)
                                            ->where('status', 1)
                                            ->count();

                if($photoCount < 1) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Foto mulai belum dilakukan',
                        'data'      => []
                    ], 403);
                }

                $photoName = $overtime->overtime_date . "_$overtimeStatusLabel" . "_" . (count($photoCheck) + 1) . '.' . $overtimePhoto->getClientOriginalExtension();
                $path = "overtime/employee/$employeeId/$photoName";

                // Hapus file lama jika ada
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }

                $pathUpload = $overtimePhoto->storeAs("overtime/employee/$employeeId", $photoName);

                $data = [
                    'overtime_id'       => $overtimeId,
                    'photo'             => $pathUpload,
                    'status'            => $status,
                    'location'          => $request->location
                ];

                // create
                $ovtPhoto = OvertimePhoto::create($data);

                return response()->json([
                    'status'    => 'success',
                    'code'      => 201,
                    'message'   => 'OK',
                    'data'      => $ovtPhoto
                ], 201);
            }

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

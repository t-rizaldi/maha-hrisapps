<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\OvertimePhoto;
use App\Models\OvertimeTracking;
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

            $branch = $this->getBranch($request->attendance_branch);

            if($branch['status'] == 'error') {
                return response()->json($branch, 200);
            }

            $branch = $branch['data'];

            // check sub or no
            if($branch['is_sub'] == 1) {
                if(strtoupper($branch['branch_parent_code']) != strtoupper($employee['branch_code'])) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Subcabang absen tidak sesuai dengan cabang karyawan'
                    ], 400);
                }
            } else {
                if(strtoupper($branch['branch_code']) != strtoupper($employee['branch_code'])) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => 'Cabang absen tidak sesuai dengan cabang karyawan'
                    ], 400);
                }
            }

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

            // overtime allowance check
            $overtimePermit = $employee['is_overtime'];

            if($overtimePermit != 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Lembur tidak di izinkan!',
                    'data'      => []
                ], 403);
            }

            $employeeJobTitle = $employee['job_title'];

            if(empty($employeeJobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jabatan karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $jobTitleRole = $employeeJobTitle['role'];

            if($jobTitleRole > 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Jabatan anda tidak diperkenankan untuk lembur!',
                    'data'      => []
                ], 403);
            }

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
                'data'      => Overtime::with(['tracking', 'photo'])->where('id', $overtime->id)->first()
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getOvertimeByEmployeeId($employeeId, Request $request)
    {
        try {
            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $overtimes = Overtime::with(['tracking', 'photo'])->where('employee_id', $employeeId);

            $byApprovedDate = "false";

            if($request->has('by_approved_date')) {
                $byApprovedDate = $request->query('by_approved_date');

                if(empty($byApprovedDate))$byApprovedDate = "false";
            }

            $whereBy = 'overtime_date';
            if($byApprovedDate == "true") $whereBy = 'approved_date';

            if($request->has('start_date')) {
                $startDate = $request->query('start_date');
                if(!empty($startDate)) {
                    $overtimes->where($whereBy, '>=', $startDate);
                }
            }

            if($request->has('end_date')) {
                $endDate = $request->query('end_date');
                if(!empty($endDate)) $overtimes->where($whereBy, '<=', $endDate);
            }

            if($request->has('approved_status')) {
                $approvedStatus = $request->query('approved_status');
                if(!empty($approvedStatus)) $overtimes->where('approved_status', $approvedStatus);
            }

            $overtimes = $overtimes->get();

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

            $employee = $employee['data'];

            // Get Overtime
            $overtime = Overtime::with(['tracking', 'photo'])
                                ->where('employee_id', $employeeId)
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

            // Check valid update
            $employeeDeptId = $employee['department_id'];
            $approvedStatus = $overtime->approved_status;

            $statusValidUpdate = [0, 11];
            if($employeeDeptId == 9) $statusValidUpdate = [2, 11];

            if(!in_array($approvedStatus, $statusValidUpdate)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Tidak bisa melakukan perubahan lembur!',
                    'data'      => []
                ], 403);
            }

            $data = [];

            if(!empty($request->start_time)) $data['start_time'] = $request->start_time;
            if(!empty($request->end_time)) $data['end_time'] = $request->end_time;

            if(!empty($data)) {
                Overtime::where('employee_id', $employeeId)
                        ->where('overtime_date', $overtimeDate)
                        ->update($data);

                $overtimeUpdate = Overtime::with(['tracking', 'photo'])
                                            ->where('employee_id', $employeeId)
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

    public function submitOvertime(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'   => 'required',
                'overtime_id'   => 'required',
                'submit_date'   => 'required|date_format:Y-m-d H:i:s'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            $employeeId = $request->employee_id;
            $overtimeId = $request->overtime_id;

            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];

            // Get Overtime
            $overtime = Overtime::with(['tracking', 'photo'])->where('id', $overtimeId)->first();

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
                ], 403);
            }

            if($overtime->approved_status != 11) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Lembur sudah diajukan sebelumnya!',
                    'data'      => []
                ], 403);
            }

            // Check Photo
            $startPhoto = OvertimePhoto::where('overtime_id', $overtime->id)
                                        ->where('status', 1)
                                        ->count();
            $progressPhoto = OvertimePhoto::where('overtime_id', $overtime->id)
                                        ->where('status', 2)
                                        ->count();
            $finishPhoto = OvertimePhoto::where('overtime_id', $overtime->id)
                                        ->where('status', 3)
                                        ->count();

            if($startPhoto < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Foto mulai tidak ada!',
                    'data'      => []
                ]);
            }

            if($progressPhoto < 3) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Foto berlangsung minimal 3!',
                    'data'      => []
                ]);
            }

            if($finishPhoto < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Foto selesai tidak ada!',
                    'data'      => []
                ]);
            }

            $approvedStatus = 0;
            $dept = $employee['department'];
            $deptId = $dept['id'];
            $gmNum = $dept['gm_num'];

            // get Approver
            $approver = $this->getApproverByStructure($employee['id']);
            $manager = $approver['manager'];
            $hrdManager = $approver['hrdManager'];
            $hrdSpv = $approver['hrdSpv'];
            $gm = $approver['gm'];
            $director = $approver['director'];
            $commisioner = $approver['commisioner'];

            //staff direktur
            if ($deptId == 2) {
                $approvedStatus = 3;
            }

            if ($deptId == 9) {
                $approvedStatus = 2;
            } else {
                // cek department under gm
                if (!empty($gmNum)) {
                    if (empty($manager)) $approvedStatus = 1;
                } else {
                    if (empty($manager)) $approvedStatus = 2;
                }
            }


            //=============================
            // cek data karyawan ada atau tidak
            if ($approvedStatus == 1) {
                if (empty($gm)) $approvedStatus = 2;
            }

            if ($approvedStatus == 2) {
                if (empty($hrdManager)) {
                    if (empty($hrdSpv)) $approvedStatus = 3;
                }
            }

            if ($approvedStatus == 3) {
                if (empty($director)) $approvedStatus = 4;
            }

            if ($approvedStatus == 4) {
                if (empty($commisioner)) $approvedStatus = 5;
            }
            //================================

            // Update overtime
            $overtime->approved_status = $approvedStatus;
            $overtime->save();

            // Create Tracking
            $data = [
                [
                    'overtime_id'   => $overtimeId,
                    'description'   => 'Lembur diajukan',
                    'datetime'      => $request->submit_date
                ],
                [
                    'overtime_id'   => $overtimeId,
                    'description'   => structureApprovalStatusLabel($approvedStatus),
                    'status'        => $approvedStatus,
                ]
            ];

            $dataTracking = [];

            foreach($data as $dat) {
                $dataTracking[] = OvertimeTracking::create($dat);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => Overtime::with(['tracking', 'photo'])->where('id', $overtimeId)->first()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function rejectOvertime(Request $request)
    {
        try  {
            $validator = Validator::make($request->all(), [
                'overtime_id'       => 'required',
                'rejector_id'       => 'required',
                'reject_statement'  => 'required',
                'reject_date'       => 'required|date_format:Y-m-d H:i:s'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Rejector
            $rejector = $this->getEmployee($request->rejector_id);

            if($rejector['status'] == 'error') {
                return response()->json($rejector, 200);
            }

            $rejector = $rejector['data'];

            if($rejector['status'] != 3) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Akun tidak aktif!',
                    'data'      => []
                ], 403);
            }

            // Get Overtime
            $overtime = Overtime::find($request->overtime_id);

            if(empty($overtime)) {
                return response()->json([
                    'ststus'    => 'error',
                    'code'      => 204,
                    'message'   => 'Lembur tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Employee
            $employee = $this->getEmployee($overtime->employee_id);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];
            $employeeDept = $employee['department'];

            $roleId = $rejector['role_id'];
            $jobTitle = $rejector['job_title'];
            $jobTitleId = $jobTitle['id'] ?? null;
            $dept = $rejector['department'];
            $deptId = $dept['id'] ?? null;

            $approvedStatus = 0;
            $newApprovedStatus = 6;

            // Check Approved Status
            if($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $approvedStatus = 2;
                $newApprovedStatus = 8;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap HRD',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Ditolak Spv HRD";
                    $overtimeTracking->description_rejected = $request->reject_statement;
                    $overtimeTracking->status = $newApprovedStatus;
                    $overtimeTracking->datetime = $request->reject_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 8;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap HRD',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Ditolak Manager HRD";
                    $overtimeTracking->description_rejected = $request->reject_statement;
                    $overtimeTracking->status = $newApprovedStatus;
                    $overtimeTracking->datetime = $request->reject_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 2 && $deptId != 9) {

                if($employee['department_id'] != $deptId) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur bukan dari departemen yang sama!',
                        'data'      => []
                    ], 403);
                }

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap manager',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Ditolak Manager";
                    $overtimeTracking->description_rejected = $request->reject_statement;
                    $overtimeTracking->status = $newApprovedStatus;
                    $overtimeTracking->datetime = $request->reject_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 3) {
                $approvedStatus = 1;
                $newApprovedStatus = 7;

                if(empty($dept)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Departemen penolak tidak ditemukan',
                        'data'      => []
                    ], 200);
                }

                if(empty($jobTitle)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Jabatan penolak tidak ditemukan',
                        'data'      => []
                    ], 200);
                }

                if(empty($employeeDept)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Departemen karyawan tidak ditemukan',
                        'data'      => []
                    ], 200);
                }

                if($jobTitle['gm_num'] != $employeeDept['gm_num']) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Karyawan bukan bawahan anda!',
                        'data'      => []
                    ], 403);
                }

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap GM',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Ditolak GM";
                    $overtimeTracking->description_rejected = $request->reject_statement;
                    $overtimeTracking->status = $newApprovedStatus;
                    $overtimeTracking->datetime = $request->reject_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 9;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap Direktur',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Ditolak Direktur";
                    $overtimeTracking->description_rejected = $request->reject_statement;
                    $overtimeTracking->status = $newApprovedStatus;
                    $overtimeTracking->datetime = $request->reject_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 10;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap komisaris',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Ditolak Komisaris";
                    $overtimeTracking->description_rejected = $request->reject_statement;
                    $overtimeTracking->status = $newApprovedStatus;
                    $overtimeTracking->datetime = $request->reject_date;
                    $overtimeTracking->save();
                }
            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Akun tidak bisa menolak lembur!',
                    'data'      => []
                ], 403);
            }

            // Update Overtime
            $overtime->approved_status = $newApprovedStatus;
            $overtime->is_read = 0;
            $overtime->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => Overtime::with(['tracking', 'photo'])->where('id', $overtime->id)->first()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function approveOvertime(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'overtime_id'       => 'required',
                'approver_id'       => 'required',
                'approved_date'     => 'required|date_format:Y-m-d H:i:s'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Approver
            $approver = $this->getEmployee($request->approver_id);

            if($approver['status'] == 'error') {
                return response()->json($approver, 200);
            }

            $approver = $approver['data'];

            if($approver['status'] != 3) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Akun tidak aktif!',
                    'data'      => []
                ], 403);
            }

            // Get Overtime
            $overtime = Overtime::find($request->overtime_id);

            if(empty($overtime)) {
                return response()->json([
                    'ststus'    => 'error',
                    'code'      => 204,
                    'message'   => 'Lembur tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Employee
            $employee = $this->getEmployee($overtime->employee_id);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];
            $employeeDept = $employee['department'];

            $roleId = $approver['role_id'];
            $jobTitle = $approver['job_title'];
            $jobTitleId = $jobTitle['id'] ?? null;
            $dept = $approver['department'];
            $deptId = $dept['id'] ?? null;

            // get Approvers by structur
            $approversData = $this->getApproverByStructure($employee['id']);
            $manager = $approversData['manager'];
            $hrdManager = $approversData['hrdManager'];
            $hrdSpv = $approversData['hrdSpv'];
            $gm = $approversData['gm'];
            $director = $approversData['director'];
            $commisioner = $approversData['commisioner'];

            $approvedStatus = 0;
            $newApprovedStatus = 2;

            // Check Approved Status
            if($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $approvedStatus = 2;
                $newApprovedStatus = 3;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap HRD',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Disetujui Spv HRD";
                    $overtimeTracking->status = $approvedStatus;
                    $overtimeTracking->datetime = $request->approved_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 3;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap HRD',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Disetujui Manager HRD";
                    $overtimeTracking->status = $approvedStatus;
                    $overtimeTracking->datetime = $request->approved_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 2 && $deptId != 9) {
                $approvedStatus = 0;

                if(empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $newApprovedStatus = 1;
                }

                if($employee['department_id'] != $deptId) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur bukan dari departemen yang sama!',
                        'data'      => []
                    ], 403);
                }

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap manager',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Disetujui Manager";
                    $overtimeTracking->status = $approvedStatus;
                    $overtimeTracking->datetime = $request->approved_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 3) {
                $approvedStatus = 1;
                $newApprovedStatus = 2;

                if(empty($dept)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Departemen penolak tidak ditemukan',
                        'data'      => []
                    ], 200);
                }

                if(empty($jobTitle)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Jabatan penolak tidak ditemukan',
                        'data'      => []
                    ], 200);
                }

                if(empty($employeeDept)) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 204,
                        'message'   => 'Departemen karyawan tidak ditemukan',
                        'data'      => []
                    ], 200);
                }

                if($jobTitle['gm_num'] != $employeeDept['gm_num']) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Karyawan bukan bawahan anda!',
                        'data'      => []
                    ], 403);
                }

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap GM',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Disetujui GM";
                    $overtimeTracking->status = $approvedStatus;
                    $overtimeTracking->datetime = $request->approved_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 4;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap Direktur',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Disetujui Direktur";
                    $overtimeTracking->status = $approvedStatus;
                    $overtimeTracking->datetime = $request->approved_date;
                    $overtimeTracking->save();
                }
            } else if($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 5;

                if($overtime->approved_status != $approvedStatus) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 403,
                        'message'   => 'Pengajuan lembur belum pada tahap komisaris',
                        'data'      => []
                    ], 403);
                }

                // Get overtime tracking
                $overtimeTracking = OvertimeTracking::where('overtime_id', $overtime->id)
                                        ->where('status', $approvedStatus)
                                        ->first();

                if(!empty($overtimeTracking)) {
                    $overtimeTracking->description = "Disetujui Komisaris";
                    $overtimeTracking->status = $approvedStatus;
                    $overtimeTracking->datetime = $request->approved_date;
                    $overtimeTracking->save();
                }
            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Akun tidak bisa menyetujui lembur!',
                    'data'      => []
                ], 403);
            }

            $trackingData = [];

            //=============================
            // cek data karyawan ada atau tidak
            if ($newApprovedStatus == 1) {
                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $trackingData = [
                        'overtime_id'   => $overtime->id,
                        'description'   => 'Diperiksa GM',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if ($newApprovedStatus == 2) {
                if (empty($hrdManager)) {
                    if (empty($hrdSpv)) {
                        $newApprovedStatus = 3;
                    } else {
                        $trackingData = [
                            'overtime_id'   => $overtime->id,
                            'description'   => 'Diperiksa HRD',
                            'status'        => $newApprovedStatus,
                            'datetime'      => $request->approved_date
                        ];
                    }
                } else {
                    $trackingData = [
                        'overtime_id'   => $overtime->id,
                        'description'   => 'Diperiksa HRD',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if ($newApprovedStatus == 3) {
                if (empty($director)) {
                    $newApprovedStatus = 4;
                } else {
                    $trackingData = [
                        'overtime_id'   => $overtime->id,
                        'description'   => 'Diperiksa Direktur',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if ($newApprovedStatus == 4) {
                if (empty($commisioner)) {
                    $newApprovedStatus = 5;
                } else {
                    $trackingData = [
                        'overtime_id'   => $overtime->id,
                        'description'   => 'Diperiksa Komisaris',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if($newApprovedStatus == 5) {
                $trackingData = [
                    'overtime_id'   => $overtime->id,
                    'description'   => 'Disetujui',
                    'status'        => $newApprovedStatus,
                    'datetime'      => $request->approved_date
                ];
            }
            //================================

            // Create overtime tracking
            if(!empty($trackingData)) {
                OvertimeTracking::create($trackingData);
            }

            // Update overtime
            $overtime->approved_status = $newApprovedStatus;
            $overtime->is_read = 0;
            if($newApprovedStatus == 5) $overtime->approved_date = $request->approved_date;
            $overtime->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => Overtime::with(['tracking', 'photo'])->where('id', $overtime->id)->first()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getOvertimeByApprover($approverId)
    {
        try {
            // Get Approver
            $approver = $this->getEmployee($approverId);

            if($approver['status'] == 'error') {
                return response()->json($approver, 200);
            }

            $approver = $approver['data'];

            if($approver['status'] != 3) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Akun tidak aktif!',
                    'data'      => []
                ], 403);
            }

            $roleId = $approver['role_id'];
            $jobTitle = $approver['job_title'];
            $jobTitleId = $jobTitle['id'] ?? null;
            $dept = $approver['department'];
            $deptId = $dept['id'] ?? null;

            // Check Approved Status
            $overtimes = [];

            if($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $overtimes = Overtime::where('approved_status', 2)
                                        ->orderBy('is_read')
                                        ->get();

                if(count($overtimes) < 1) $overtimes = [];

            } else if($roleId == 2 && $deptId == 9) {
                $overtimes = Overtime::where('approved_status', 2)
                                        ->orderBy('is_read')
                                        ->get();

                if(count($overtimes) < 1) $overtimes = [];

            } else if($roleId == 2 && $deptId != 9) {
                $params = [
                    'role_id'           => 1,
                    'department_code'   => $dept['department_code'],
                    'status'            => 3
                ];

                $employees = $this->getEmployeeByParams($params);

                if($employees['status'] == 'error') {
                    return response()->json($employees, 200);
                }

                $employees = $employees['data'];

                // get overtime data
                foreach($employees as $employee) {
                    $overtime = Overtime::where('approved_status', 0)
                                        ->where('employee_id', $employee['id'])
                                        ->orderBy('is_read')
                                        ->first();

                    if(!empty($overtime)) $overtimes[] = $overtime;
                }

            } else if($roleId == 3) {
                // get department under gm
                $departments = $this->getAllDepartment(['gm_num' => $jobTitle['gm_num']]);

                if($departments['status'] == 'error') {
                    return response()->json($departments, 200);
                }

                $departments = $departments['data'];

                $params = [];

                foreach($departments as $department) {
                    $params[] = [
                        'role_id'           => 1,
                        'department_code'   => $department['department_code'],
                        'status'            => 3
                    ];
                }

                $employeeData = [];

                // Get Employee
                foreach($params as $param) {
                    $employees = $this->getEmployeeByParams($param);

                    if($employees['status'] == 'success') {
                        foreach($employees['data'] as $emp) {
                            $employeeData[] = $emp;
                        }
                    }
                }

                // get overtime data
                foreach($employeeData as $employee) {
                    $overtime = Overtime::where('approved_status', 1)
                                        ->where('employee_id', $employee['id'])
                                        ->orderBy('is_read')
                                        ->first();

                    if(!empty($overtime)) $overtimes[] = $overtime;
                }

            } else if($roleId == 4) {
                $overtimes = Overtime::where('approved_status', 3)
                                        ->orderBy('is_read')
                                        ->get();

                if(count($overtimes) < 1) $overtimes = [];
            } else if($roleId == 5) {
                $overtimes = Overtime::where('approved_status', 4)
                                        ->orderBy('is_read')
                                        ->get();

                if(count($overtimes) < 1) $overtimes = [];

            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Tidak bisa mengakses daftar lembur karyawan lain!',
                    'data'      => []
                ], 403);
            }

            if(empty($overtimes)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Data lembur tidak ditemukan!',
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

    public function overtimeOrderStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'boss_id'           => 'required',
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
                    'message'   => 'Sudah ada lembur yang diinput pada tanggal yang sama',
                    'data'      => []
                ], 400);
            }

            // Get Boss
            $boss = $this->getEmployee($request->boss_id);

            if($boss['status'] == 'error') {
                return response()->json($boss, 200);
            }

            $boss = $boss['data'];
            $bossDept = $boss['department'];
            $bossJobTitle = $boss['job_title'];
            $bossRoleId = $boss['role_id'];

            if(empty($bossDept)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Departemen atasan tidak ditemukan!',
                    'data'      => []
                ], 200);
            }

            if(empty($bossJobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jabatan atasan tidak ditemukan!',
                    'data'      => []
                ], 200);
            }

            // Get Employee
            $employee = $this->getEmployee($request->employee_id);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];
            $employeeDept = $employee['department'];

            if(empty($employeeDept)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Departemen karyawan tidak ditemukan!',
                    'data'      => []
                ], 200);
            }

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

            // Boss role check
            if($bossRoleId == 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Tidak memiliki akses perintah lembur!',
                    'data'      => []
                ], 403);
            }

            if($bossRoleId == 2 && $bossDept['id'] != $employeeDept['id']) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Karyawan bukan bawahan anda!',
                    'data'      => []
                ], 403);
            }

            if($bossRoleId == 3 && $bossJobTitle['gm_num'] != $employeeDept['gm_num']) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Karyawan bukan bawahan anda!',
                    'data'      => []
                ], 403);
            }

            $data = [
                'employee_id'       => $request->employee_id,
                'boss_id'           => $request->boss_id,
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
                'data'      => Overtime::with(['tracking', 'photo'])->where('id', $overtime->id)->first()
            ], 201);

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

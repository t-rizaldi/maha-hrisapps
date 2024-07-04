<?php

namespace App\Http\Controllers;

use App\Models\PermitType;
use App\Models\PermitApplication;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Http\Resources\ApiResponse;
use App\Models\PermitTracking;
use Carbon\Carbon;
use Exception;


class PermitController extends BaseController
{
    /*=================================
                Permission Type
    =================================*/

    public function getAllPermitType()
    {
        $permitTypes = PermitType::all();
        if ($permitTypes->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $permitTypes);
    }

    public function getPermitTypeByID($id)
    {
        $permitType = PermitType::find($id);
        if ($permitType === null) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $permitType);
    }

    public function getPermitByType(Request $request)
    {
        $type = $request->query('type');
        if ($type) {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:i,c',
            ]);
            if ($validator->fails()) {
                return new ApiResponse($this->messageError, 400, $validator->errors());
            }
            $permitTypes = PermitType::where('type', $type)->get();
        } else {
            $permitTypes = PermitType::all();
        }

        if ($permitTypes->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $permitTypes);
    }

    public function storePermitType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:i,c',
            'name' => 'required|string',
            'total_day' => 'required|integer',
            'is_yearly' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $data = [
            'type' => $request->type,
            'name' => $request->name,
            'total_day' => $request->total_day,
            'is_yearly' => $request->is_yearly,
        ];
        $permitType = PermitType::create($data);
        return new ApiResponse($this->messageSuccess, 201, 'Jenis izin' . $this->messageCreated, $permitType);
    }

    public function updatePermitType(Request $request, $id)
    {
        $permitType = PermitType::find($id);
        if ($permitType === null) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin' . $this->messageNotFound);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:i,c',
            'name' => 'required|string',
            'total_day' => 'required|integer',
            'is_yearly' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $data = [
            'type' => $request->type,
            'name' => $request->name,
            'total_day' => $request->total_day,
            'is_yearly' => $request->is_yearly,
        ];
        $permitType->update($data);
        return new ApiResponse($this->messageSuccess, 200, 'Jenis izin' . $this->messageUpdated, $permitType);
    }

    public function deletePermitType($id)
    {
        $permitType = PermitType::find($id);
        if ($permitType === null) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin' . $this->messageNotFound);
        }
        $permitType->delete();
        return new ApiResponse($this->messageSuccess, 200, 'Jenis izin' . $this->messageDeleted);
    }


    /*=================================
                  Permit\
    =================================*/

    public function getAllPermit()
    {
        $permit = PermitApplication::with('permitType')->get();
        if ($permit->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Izin' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $permit);
    }

    public function getPermitByID($id)
    {
        $permit = PermitApplication::with('permitType')->find($id);
        if (
            $permit === null
        ) {
            return new ApiResponse($this->messageError, 200, 'Izin' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $permit);
    }

    public function getPermitByEmployeeID($id)
    {
        $permit = PermitApplication::with('permitType')->where('employee_id', $id)->get();
        if ($permit->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Izin' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $permit);
    }

    public function getAllPermitByApprover($approverId)
    {
        try {
            // Get Approver
            $approver = $this->getEmployee($approverId);
            if ($approver['status'] == 'error') {
                return response()->json($approver, 200);
            }
            $approver = $approver['data'];

            if ($approver['status'] != 3) {
                return new ApiResponse($this->messageError, 403, 'Akun tidak aktif!');
            }

            $roleId = $approver['role_id'];
            $jobTitle = $approver['job_title'];
            $jobTitleId = $jobTitle['id'] ?? null;
            $dept = $approver['department'];
            $deptId = $dept['id'] ?? null;

            // Check Approved Status
            $permits = [];
            if ($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $permits = PermitApplication::where('approved_status', 2)
                    ->orderBy('is_read')
                    ->get();
                if (count($permits) < 1) $permits = [];
            } else if (
                $roleId == 2 && $deptId == 9
            ) {
                $permits = PermitApplication::where('approved_status', 2)
                    ->orderBy('is_read')
                    ->get();
                if (count($permits) < 1) $permits = [];
            } else if (
                $roleId == 2 && $deptId != 9
            ) {
                $params = [
                    'role_id'           => 1,
                    'department_code'   => $dept['department_code'],
                    'status'            => 3
                ];
                $employees = $this->getEmployeeByParams($params);
                if ($employees['status'] == 'error') {
                    return response()->json($employees, 200);
                }
                $employees = $employees['data'];
                // get permit data
                foreach ($employees as $employee) {
                    $employeePermits = PermitApplication::where('approved_status', 0)
                        ->where('employee_id', $employee['id'])
                        ->orderBy('is_read')
                        ->get();

                    if ($employeePermits->isNotEmpty()) {
                        foreach ($employeePermits as $permit) {
                            $permits[] = $permit;
                        }
                    }
                }
            } else if ($roleId == 3) {
                // get department under gm
                $departments = $this->getAllDepartment(['gm_num' => $jobTitle['gm_num']]);
                if ($departments['status'] == 'error') {
                    return response()->json($departments, 200);
                }
                $departments = $departments['data'];
                $params = [];
                foreach ($departments as $department) {
                    $params[] = [
                        'role_id'           => 1,
                        'department_code'   => $department['department_code'],
                        'status'            => 3
                    ];
                }

                $employeeData = [];
                // Get Employee
                foreach ($params as $param) {
                    $employees = $this->getEmployeeByParams($param);
                    if ($employees['status'] == 'success') {
                        foreach ($employees['data'] as $emp) {
                            $employeeData[] = $emp;
                        }
                    }
                }

                // get permit data
                foreach ($employeeData as $employee) {
                    $employeePermits = PermitApplication::where('approved_status', 1)
                        ->where('employee_id', $employee['id'])
                        ->orderBy('is_read')
                        ->get();

                    if ($employeePermits->isNotEmpty()) {
                        foreach ($employeePermits as $permit) {
                            $permits[] = $permit;
                        }
                    }
                }
            } else if ($roleId == 4) {
                $permits = PermitApplication::where('approved_status', 3)
                    ->orderBy('is_read')
                    ->get();

                if (count($permits) < 1) $permits = [];
            } else if (
                $roleId == 5
            ) {
                $permits = PermitApplication::where('approved_status', 4)
                    ->orderBy('is_read')
                    ->get();

                if (count($permits) < 1) $permits = [];
            } else {
                return new ApiResponse($this->messageError, 403, 'Tidak bisa mengakses daftar izin karyawan lain!');
            }

            if (empty($permits)) {
                return new ApiResponse($this->messageError, 200, 'Data izin' . $this->messageNotFound);
            }

            return new ApiResponse($this->messageSuccess, 200, 'Ok', $permits);
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }


    public function storePermit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'permit_type_id' => 'required',
            'employee_create_id' => 'required',
            'permit_start_date' => 'required|date_format:Y-m-d',
            'permit_end_date' => 'nullable|date_format:Y-m-d',
            'permit_start_time' => 'nullable|date_format:H:i',
            'permit_end_time' => 'nullable|date_format:H:i',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5048',
        ], [
            'required' => 'Field :attribute wajib diisi.',
            'date_format' => 'Format :attribute harus :format.',
            'file' => 'Field :attribute harus berupa file.',
            'mimes' => 'Field :attribute harus berupa file dengan tipe: :values.',
            'max' => 'Field :attribute tidak boleh lebih dari :max kilobytes.',
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }
        $employeeId = $request->employee_id;
        $existingPermit = PermitApplication::where('employee_id', $employeeId)
            ->where('permit_start_date', $request->permit_start_date)
            ->first();

        if ($existingPermit) {
            return new ApiResponse($this->messageError, 400, 'Tanggal awal izin sudah ada untuk karyawan ini !');
        }

        $permitTypeID = $request->permit_type_id;
        $permitData = PermitType::find($permitTypeID);
        if(empty($permitData)) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin tidak ditemukan!');
        }
        return $this->savePermit($request);
    }

    public function updatePermit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'permit_type_id' => 'required',
            'employee_create_id' => 'required',
            'permit_start_date' => 'required|date_format:Y-m-d',
            'permit_end_date' => 'nullable|date_format:Y-m-d',
            'permit_start_time' => 'nullable|date_format:H:i',
            'permit_end_time' => 'nullable|date_format:H:i',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5048',
        ], [
            'required' => 'Field :attribute wajib diisi.',
            'date_format' => 'Format :attribute harus :format.',
            'file' => 'Field :attribute harus berupa file.',
            'mimes' => 'Field :attribute harus berupa file dengan tipe: :values.',
            'max' => 'Field :attribute tidak boleh lebih dari :max kilobytes.',
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $permit = PermitApplication::find($id);
        if (!$permit) {
            return new ApiResponse($this->messageError, 404, 'Izin' . $this->messageNotFound);
        }

        $permitTypeID = $request->permit_type_id;
        $permitData = PermitType::find($permitTypeID);
        if(empty($permitData)) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin tidak ditemukan!');
        }
        return $this->savePermit($request, $permit);
    }

    private function savePermit(Request $request, $permit = null)
    {
        $permitTypeID = $request->permit_type_id;
        if($permitTypeID != 10 || $permitTypeID != 11) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin tidak valid !');
        }

        $employeeId = $request->employee_id;
        $employee = $this->getEmployee($employeeId);
        if ($employee['status'] == 'error') {
            return response()->json($employee, 200);
        }
        $employee = $employee['data'];
        if (empty($employee['branch_code'])) {
            return new ApiResponse($this->messageError, 200, 'Kode Cabang Karyawan Kosong !');
        }
        $branch = $this->getBranch($employee['branch_code']);
        if ($branch['status'] == 'error') {
            return response()->json($branch, 200);
        }
        $branch = $branch['data'];
        $branchCode = $branch['branch_code'];

        if (
            $request->permit_end_date != null && ($request->permit_start_time != null || $request->permit_end_time != null)
        ) {
            return new ApiResponse($this->messageError, 400, 'Tanggal akhir wajib kosong jika jam awal atau akhir diisi !');
        }

        if (
            $request->permit_end_date == null && $request->permit_start_date != null && ($request->permit_start_time == null || $request->permit_end_time == null)
        ) {
            return new ApiResponse($this->messageError, 400, 'Jam awal dan akhir harus diisi jika tanggal akhir kosong!');
        }

        if ($request->permit_start_time != null && $request->permit_end_time != null) {
            $permitStartTime = Carbon::createFromFormat('H:i', $request->permit_start_time);
            $permitEndTime = Carbon::createFromFormat('H:i', $request->permit_end_time);
            if ($permitStartTime->gt($permitEndTime)) {
                return new ApiResponse($this->messageError, 400, 'Jam awal tidak boleh lebih dari Jam Akhir !');
            }
        }

        $permitStartDate = Carbon::parse($request->permit_start_date);
        $permitEndDate = $request->permit_end_date ? Carbon::parse($request->permit_end_date) : $permitStartDate;
        if ($permitEndDate->lt($permitStartDate)) {
            return new ApiResponse($this->messageError, 400, 'Tanggal akhir tidak boleh kurang dari tanggal awal !');
        }

        $distribution =  distributionPermitDay($permitStartDate, $permitEndDate);
        $totalFirstMonth = $distribution['firstMonth'];
        $totalSecondMonth = $distribution['secondMonth'];
        $totalDays = $permitStartDate->eq($permitEndDate) ? 1 : $totalFirstMonth + $totalSecondMonth;

        $attachment = $request->file('attachment');

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

        if (
            $approvedStatus == 4
        ) {
            if (empty($commisioner)) $approvedStatus = 5;
        }
        //================================

        $data = [
            'employee_id' => $request->employee_id,
            'permit_type_id' => $request->permit_type_id,
            'employee_create_id' => $request->employee_create_id,
            'permit_start_date' => $request->permit_start_date,
            'permit_end_date' => $request->permit_end_date,
            'permit_start_time' => $request->permit_start_time,
            'permit_end_time' => $request->permit_end_time,
            'description' => $request->description,
            'total_day' => $totalDays,
            'total_first_month' => $totalFirstMonth,
            'total_second_month' => $totalSecondMonth,
            'permit_branch' => $branchCode,
            'approved_status' => $approvedStatus,
        ];
        if ($attachment) {
            $attachmentName = Str::uuid() . '-' . $attachment->getClientOriginalName();
            $path = "permit/employee/$employeeId/$attachmentName";
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            $pathUpload = $attachment->storeAs("permit/employee/$employeeId", $attachmentName);
            $data['attachment'] = $pathUpload;
        }
        if ($permit) {
            if ($attachment) {
                $storage = $permit->attachment;
                if ($storage) {
                    if(Storage::exists($storage)) Storage::delete($storage);
                }
            } else {
                $data['attachment'] = $permit->attachment;
            }
            $permit->update($data);
        } else {
            $permitCreate = PermitApplication::create($data);
            $permitCreate->approved_status = $approvedStatus;
            $permitCreate->save();
            $submitDate = Carbon::now()->format('Y-m-d H:i:s');
            $data = [
                [
                    'permit_id'   => $permitCreate->id,
                    'description'   => 'Izin berhasil diajukan',
                    'datetime'      => $submitDate
                ],
                [
                    'permit_id'   => $permitCreate->id,
                    'description'   => structureApprovalStatusLabel($approvedStatus),
                    'status'        => $approvedStatus,
                ]
            ];
            $dataTracking = [];
            foreach ($data as $dt) {
                $dataTracking[] = PermitTracking::create($dt);
            }
        }
        return new ApiResponse($this->messageSuccess, $permit ? 200 : 201, 'Izin Karyawan' . ($permit ? $this->messageUpdated : $this->messageCreated), $permit ? $permit : $permitCreate);
    }

    public function deletePermit($id)
    {
        $permit = PermitApplication::find($id);

        if (empty($permit)) {
            return new ApiResponse($this->messageError, 200, 'Izin Karyawan' . $this->messageNotFound);
        }

        $storage = $permit->attachment;
        if ($storage) {
            if(Storage::exists($storage)) Storage::delete($storage);
        }

        $permit->delete();
        return new ApiResponse($this->messageSuccess, 200, 'Izin Karyawan' . $this->messageDeleted);
    }

    public function approvePermit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'permit_id'       => 'required',
                'approver_id'       => 'required',
                'approved_date'     => 'required|date_format:Y-m-d H:i:s'
            ]);

            if ($validator->fails()) {
                return new ApiResponse($this->messageError, 400, $validator->errors());
            }

            // Get Approver
            $approver = $this->getEmployee($request->approver_id);

            if ($approver['status'] == 'error') {
                return response()->json($approver, 200);
            }

            $approver = $approver['data'];

            if ($approver['status'] != 3) {
                return new ApiResponse($this->messageError, 403, 'Akun tidak aktif!');
            }

            // Get Permit
            $permit = PermitApplication::find($request->permit_id);

            if (empty($permit)) {
                return new ApiResponse($this->messageError, 200, 'Data izin' . $this->messageNotFound);
            }

            // Get Employee
            $employee = $this->getEmployee($permit->employee_id);

            if ($employee['status'] == 'error') {
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
            if ($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $approvedStatus = 2;
                $newApprovedStatus = 3;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap HRD');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Disetujui Spv HRD";
                    $permitTracking->status = $approvedStatus;
                    $permitTracking->datetime = $request->approved_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 3;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap HRD');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Disetujui Manager HRD";
                    $permitTracking->status = $approvedStatus;
                    $permitTracking->datetime = $request->approved_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 2 && $deptId != 9) {
                $approvedStatus = 0;

                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $newApprovedStatus = 1;
                }

                if ($employee['department_id'] != $deptId) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin bukan dari departemen yang sama!');
                }

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap Manager');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Disetujui Manager";
                    $permitTracking->status = $approvedStatus;
                    $permitTracking->datetime = $request->approved_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 3) {
                $approvedStatus = 1;
                $newApprovedStatus = 2;

                if (empty($dept)) {
                    return new ApiResponse($this->messageError, 200, 'Departemen penolak' . $this->messageNotFound);
                }

                if (empty($jobTitle)) {
                    return new ApiResponse($this->messageError, 200, 'Jabatan penolak' . $this->messageNotFound);
                }

                if (empty($employeeDept)) {
                    return new ApiResponse($this->messageError, 200, 'Departemen karyawan' . $this->messageNotFound);
                }

                if ($jobTitle['gm_num'] != $employeeDept['gm_num']) {
                    return new ApiResponse($this->messageError, 403, 'Karyawan bukan bawahan anda!');
                }

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap GM');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Disetujui GM";
                    $permitTracking->status = $approvedStatus;
                    $permitTracking->datetime = $request->approved_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 4;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap Direktur');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Disetujui Direktur";
                    $permitTracking->status = $approvedStatus;
                    $permitTracking->datetime = $request->approved_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 5;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap Komisaris');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Disetujui Komisaris";
                    $permitTracking->status = $approvedStatus;
                    $permitTracking->datetime = $request->approved_date;
                    $permitTracking->save();
                }
            } else {
                return new ApiResponse($this->messageError, 403, 'Anda tidak memiliki akses untuk menyetujui izin');
            }

            $trackingData = [];

            //=============================
            // cek data karyawan ada atau tidak
            if ($newApprovedStatus == 1) {
                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $trackingData = [
                        'permit_id'   => $permit->id,
                        'description'   => 'Diperiksa GM',
                        'status'        => $newApprovedStatus,
                    ];
                }
            }

            if ($newApprovedStatus == 2) {
                if (empty($hrdManager)) {
                    if (empty($hrdSpv)) {
                        $newApprovedStatus = 3;
                    } else {
                        $trackingData = [
                            'permit_id'   => $permit->id,
                            'description'   => 'Diperiksa HRD',
                            'status'        => $newApprovedStatus,
                            'datetime'      => $request->approved_date
                        ];
                    }
                } else {
                    $trackingData = [
                        'permit_id'   => $permit->id,
                        'description'   => 'Diperiksa HRD',
                        'status'        => $newApprovedStatus
                    ];
                }
            }

            if ($newApprovedStatus == 3) {
                if (empty($director)) {
                    $newApprovedStatus = 4;
                } else {
                    $trackingData = [
                        'permit_id'   => $permit->id,
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
                        'permit_id'   => $permit->id,
                        'description'   => 'Diperiksa Komisaris',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if ($newApprovedStatus == 5) {
                $trackingData = [
                    'permit_id'   => $permit->id,
                    'description'   => 'Disetujui',
                    'status'        => $newApprovedStatus,
                    'datetime'      => $request->approved_date
                ];
            }
            //================================

            // Create permit tracking
            if (!empty($trackingData)) {
                PermitTracking::create($trackingData);
            }

            // Update permit
            $permit->approved_status = $newApprovedStatus;
            $permit->is_read = 0;
            $permit->save();

            return new ApiResponse($this->messageSuccess, 200, 'Izin karyawan berhasil diterima !', PermitApplication::with(['tracking'])->where('id', $permit->id)->first());
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }

    public function rejectPermit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'permit_id'       => 'required',
                'rejector_id'       => 'required',
                'reject_statement'  => 'required',
                'reject_date'       => 'required|date_format:Y-m-d H:i:s'
            ]);

            if ($validator->fails()) {
                return new ApiResponse($this->messageError, 400, $validator->errors());
            }
            // Get Rejector
            $rejector = $this->getEmployee($request->rejector_id);
            if ($rejector['status'] == 'error') {
                return response()->json($rejector, 200);
            }
            $rejector = $rejector['data'];
            if ($rejector['status'] != 3) {
                return new ApiResponse($this->messageError, 403, 'Akun tidak aktif!');
            }
            // Get Permit
            $permit = PermitApplication::find($request->permit_id);

            if (empty($permit)) {
                return new ApiResponse($this->messageError, 200, 'Data izin' . $this->messageNotFound);
            }

            // Get Employee
            $employee = $this->getEmployee($permit->employee_id);

            if ($employee['status'] == 'error') {
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
            if ($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $approvedStatus = 2;
                $newApprovedStatus = 8;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap HRD');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Ditolak Spv HRD";
                    $permitTracking->description_rejected = $request->reject_statement;
                    $permitTracking->status = $newApprovedStatus;
                    $permitTracking->datetime = $request->reject_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 8;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap HRD');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Ditolak Manager HRD";
                    $permitTracking->description_rejected = $request->reject_statement;
                    $permitTracking->status = $newApprovedStatus;
                    $permitTracking->datetime = $request->reject_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 2 && $deptId != 9) {
                if ($employee['department_id'] != $deptId) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin bukan dari departemen yang sama!');
                }

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap Manager');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Ditolak Manager";
                    $permitTracking->description_rejected = $request->reject_statement;
                    $permitTracking->status = $newApprovedStatus;
                    $permitTracking->datetime = $request->reject_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 3) {
                $approvedStatus = 1;
                $newApprovedStatus = 7;

                if (empty($dept)) {
                    return new ApiResponse($this->messageError, 200, 'Departemen penolak' . $this->messageNotFound);
                }

                if (empty($jobTitle)) {
                    return new ApiResponse($this->messageError, 200, 'Jabatan penolak' . $this->messageNotFound);
                }

                if (empty($employeeDept)) {
                    return new ApiResponse($this->messageError, 200, 'Departemen karyawan' . $this->messageNotFound);
                }

                if ($jobTitle['gm_num'] != $employeeDept['gm_num']) {
                    return new ApiResponse($this->messageError, 403, 'Karyawan bukan bawahan anda!');
                }

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap GM');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Ditolak GM";
                    $permitTracking->description_rejected = $request->reject_statement;
                    $permitTracking->status = $newApprovedStatus;
                    $permitTracking->datetime = $request->reject_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 9;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap Direktur');
                }

                // Get permit tracking
                $permitTracking = permitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Ditolak Direktur";
                    $permitTracking->description_rejected = $request->reject_statement;
                    $permitTracking->status = $newApprovedStatus;
                    $permitTracking->datetime = $request->reject_date;
                    $permitTracking->save();
                }
            } else if ($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 10;

                if ($permit->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan izin belum pada tahap Komisaris');
                }

                // Get permit tracking
                $permitTracking = PermitTracking::where('permit_id', $permit->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($permitTracking)) {
                    $permitTracking->description = "Ditolak Komisaris";
                    $permitTracking->description_rejected = $request->reject_statement;
                    $permitTracking->status = $newApprovedStatus;
                    $permitTracking->datetime = $request->reject_date;
                    $permitTracking->save();
                }
            } else {
                return new ApiResponse($this->messageError, 403, 'Anda tidak memiliki akses untuk menolak izin');
            }
            // Update permit
            $permit->approved_status = $newApprovedStatus;
            $permit->is_read = 0;
            $permit->save();
            return new ApiResponse($this->messageSuccess, 200, 'Izin karyawan berhasil ditolak !', PermitApplication::with(['tracking'])->where('id', $permit->id)->first());
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }
}

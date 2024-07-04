<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResponse;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveTracking;
use App\Models\PermitType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class LeaveController extends BaseController
{
    public function getAllLeave()
    {
        $leave = LeaveApplication::with(['tracking'])->get();
        if ($leave->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Cuti' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'Ok', $leave);
    }

    public function getLeaveByID($id)
    {
        $leave = LeaveApplication::find($id);
        if (!$leave) {
            return new ApiResponse($this->messageError, 200, 'Cuti' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'Ok', $leave);
    }

    public function getLeaveByEmployeeID($id)
    {
        $leave = LeaveApplication::where('employee_id', $id)->get();
        if ($leave->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Cuti' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'Ok', $leave);
    }

    public function getAllLeaveByApprover($approverId)
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
            $leaves = [];
            if ($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $leaves = LeaveApplication::where('approved_status', 2)
                    ->orderBy('is_read')
                    ->get();
                if (count($leaves) < 1) $leaves = [];

            } else if ($roleId == 2 && $deptId == 9) {
                $leaves = LeaveApplication::where('approved_status', 2)
                    ->orderBy('is_read')
                    ->get();
                if (count($leaves) < 1) $leaves = [];

            } else if ($roleId == 2 && $deptId != 9) {
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
                // get leave data
                foreach ($employees as $employee) {
                    $employeeLeaves = LeaveApplication::where('approved_status', 0)
                        ->where('employee_id', $employee['id'])
                        ->orderBy('is_read')
                        ->get();

                    if ($employeeLeaves->isNotEmpty()) {
                        foreach ($employeeLeaves as $leave) {
                            $leaves[] = $leave;
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

                // get leave data
                foreach ($employeeData as $employee) {
                    $employeeLeaves = LeaveApplication::where('approved_status', 1)
                        ->where('employee_id', $employee['id'])
                        ->orderBy('is_read')
                        ->get();

                    if ($employeeLeaves->isNotEmpty()) {
                        foreach ($employeeLeaves as $leave) {
                            $leaves[] = $leave;
                        }
                    }
                }
            } else if ($roleId == 4) {
                $leaves = LeaveApplication::where('approved_status', 3)
                    ->orderBy('is_read')
                    ->get();

                if (count($leaves) < 1) $leaves = [];
            } else if ($roleId == 5) {
                $leaves = LeaveApplication::where('approved_status', 4)
                    ->orderBy('is_read')
                    ->get();

                if (count($leaves) < 1) $leaves = [];
            } else {
                return new ApiResponse($this->messageError, 403, 'Tidak bisa mengakses daftar Cuti karyawan lain!');
            }

            if (empty($leaves)) {
                return new ApiResponse($this->messageError, 200, 'Data Cuti' . $this->messageNotFound);
            }

            return new ApiResponse($this->messageSuccess, 200, 'Ok', $leaves);
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }


    public function storeLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'employee_create_id' => 'required',
            'permit_type_id' => 'required',
            'leave_start_date' => 'required|date_format:Y-m-d',
            'leave_end_date' => 'nullable|date_format:Y-m-d',
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

        $permitTypeID = $request->permit_type_id;
        $permitData = PermitType::find($permitTypeID);
        $leaveStartDate = $request->leave_start_date;
        $leaveEndDate = $request->leave_end_date;

        if(empty($permitData)) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin tidak ditemukan!');
        }


        if ($permitTypeID == 1) {
            if ($leaveEndDate == null) {
                return new ApiResponse($this->messageError, 400, 'Tanggal akhir Wajib Diisi !');
            }
        } else {
            if ($leaveEndDate != null) {
                return new ApiResponse($this->messageError, 400, 'Tanggal akhir Wajib Kosong !');
            }
            $leaveStartDate = $request->leave_start_date;
            $totalDays = $permitData->total_day;

            $startConvert = Carbon::parse($leaveStartDate);
            $leaveEndDate = $startConvert->copy();

            $daysCounted = 0;
            while ($daysCounted < $totalDays) {
                if (!$leaveEndDate->isSunday() && !Holiday::where('holidays_date', $leaveEndDate->toDateString())->exists()) {
                    $daysCounted++;
                }
                if ($daysCounted < $totalDays) {
                    $leaveEndDate->addDay();
                }
            }

            while ($leaveEndDate->isSunday() || Holiday::where('holidays_date', $leaveEndDate->toDateString())->exists()) {
                $leaveEndDate->addDay();
            }
        }

        return $this->saveLeave($request, $leaveEndDate);
    }

    public function updateLeave(Request $request, $id)
    {

        $leave = LeaveApplication::find($id);
        if ($leave === null) {
            return new ApiResponse($this->messageError, 200, 'Cuti' . $this->messageNotFound);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'employee_create_id' => 'required',
            'permit_type_id' => 'required',
            'leave_start_date' => 'required|date_format:Y-m-d',
            'leave_end_date' => 'nullable|date_format:Y-m-d',
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

        $permitTypeID = $request->permit_type_id;
        $permitData = PermitType::find($permitTypeID);
        $leaveStartDate = $request->leave_start_date;
        $leaveEndDate = $request->leave_end_date;

        if(empty($permitData)) {
            return new ApiResponse($this->messageError, 200, 'Jenis izin tidak ditemukan!');
        }

        if ($permitTypeID == 1) {
            if ($leaveEndDate == null) {
                return new ApiResponse($this->messageError, 400, 'Tanggal akhir Wajib Diisi !');
            }
        } else {
            if ($leaveEndDate != null) {
                return new ApiResponse($this->messageError, 400, 'Tanggal akhir Wajib Kosong !');
            }
            $leaveStartDate = $request->leave_start_date;
            $totalDays = $permitData->total_day;

            $startConvert = Carbon::parse($leaveStartDate);
            $leaveEndDate = $startConvert->copy();

            $daysCounted = 0;
            //Menambah hari jika di range tanggal ada hari libur atau minggu
            while ($daysCounted < $totalDays) {
                if (!$leaveEndDate->isSunday() && !Holiday::where('holidays_date', $leaveEndDate->toDateString())->exists()) {
                    $daysCounted++;
                }
                if ($daysCounted < $totalDays) {
                    $leaveEndDate->addDay();
                }
            }

            //Menambah hari jika di range tanggal akhir ada hari libur atau minggu
            while ($leaveEndDate->isSunday() || Holiday::where('holidays_date', $leaveEndDate->toDateString())->exists()) {
                $leaveEndDate->addDay();
            }
        }

        return $this->saveLeave($request, $leaveEndDate, $leave);
    }

    private function saveLeave(Request $request, $leaveEndDateFunction, $leave = null)
    {
        $permitTypeID = $request->permit_type_id;
        if($permitTypeID == 10 || $permitTypeID == 11) {
            return new ApiResponse($this->messageError, 200, 'Jenis cuti tidak valid !');
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


        $leaveStartDate = Carbon::parse($request->leave_start_date);
        $leaveEndDate =  Carbon::parse($request->leave_end_date ? $request->leave_end_date : $leaveEndDateFunction);
        if ($leaveEndDate->lt($leaveStartDate)) {
            return new ApiResponse($this->messageError, 400, 'Tanggal akhir tidak boleh kurang dari tanggal awal !');
        }

        $distribution =  distributionPermitDay($leaveStartDate, $leaveEndDate);
        $totalFirstMonth = $distribution['firstMonth'];
        $totalSecondMonth = $distribution['secondMonth'];
        $totalDays = $leaveStartDate->eq($leaveEndDate) ? 1 : $totalFirstMonth + $totalSecondMonth;

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
            'leave_start_date' => $request->leave_start_date,
            'leave_end_date' => $leaveEndDate->toDateString(),
            'description' => $request->description,
            'total_day' => $totalDays,
            'total_first_month' => $totalFirstMonth,
            'total_second_month' => $totalSecondMonth,
            'leave_branch' => $branchCode,
            'approved_status' => $approvedStatus,
        ];

        if ($attachment) {
            $attachmentName = Str::uuid() . '-' . $attachment->getClientOriginalName();
            $path = "leave/employee/$employeeId/$attachmentName";
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            $pathUpload = $attachment->storeAs("leave/employee/$employeeId", $attachmentName);
            $data['attachment'] = $pathUpload;
        }

        if ($leave) {
            $leaveStartDateRange = Carbon::parse($request->leave_start_date);
            $leaveEndDateRange = Carbon::parse($leaveEndDate);
            $existingLeaves = LeaveApplication::where('employee_id', $employeeId)
                ->where('id', '!=', $leave->id)
                ->where(function ($query) use ($leaveStartDateRange, $leaveEndDateRange) {
                    $query->whereBetween('leave_start_date', [$leaveStartDateRange, $leaveEndDateRange])
                        ->orWhereBetween('leave_end_date', [$leaveStartDateRange, $leaveEndDateRange])
                        ->orWhere(function ($query) use ($leaveStartDateRange, $leaveEndDateRange) {
                            $query->where('leave_start_date', '<=', $leaveStartDateRange)
                                ->where('leave_end_date', '>=', $leaveEndDateRange);
                        });
                })
                ->exists();

            if ($existingLeaves) {
                return new ApiResponse($this->messageError, 400, 'Jarak Tanggal awal dan akhir cuti sudah ada !');
            }
            if ($attachment) {
                $storage = $leave->attachment;
                if ($storage) {
                    if(Storage::exists($storage)) Storage::delete($storage);
                }
            } else {
                $data['attachment'] = $leave->attachment;
            }
            $leave->update($data);
        } else {
            $leaveStartDateRange = Carbon::parse($request->leave_start_date);
            $leaveEndDateRange = Carbon::parse($leaveEndDate);
            $existingLeaves = LeaveApplication::where('employee_id', $employeeId)
                ->where(function ($query) use ($leaveStartDateRange, $leaveEndDateRange) {
                    $query->whereBetween('leave_start_date', [$leaveStartDateRange, $leaveEndDateRange])
                        ->orWhereBetween('leave_end_date', [$leaveStartDateRange, $leaveEndDateRange])
                        ->orWhere(function ($query) use ($leaveStartDateRange, $leaveEndDateRange) {
                            $query->where('leave_start_date', '<=', $leaveStartDateRange)
                                ->where('leave_end_date', '>=', $leaveEndDateRange);
                        });
                })
                ->exists();

            if ($existingLeaves) {
                return new ApiResponse($this->messageError, 400, 'Jarak Tanggal awal dan akhir cuti sudah ada !');
            }
            $leaveCreate = LeaveApplication::create($data);
            $leaveCreate->approved_status = $approvedStatus;
            $leaveCreate->save();
            $submitDate = Carbon::now()->format('Y-m-d H:i:s');
            $data = [
                [
                    'leave_id'   => $leaveCreate->id,
                    'description'   => 'Cuti berhasil diajukan',
                    'datetime'      => $submitDate
                ],
                [
                    'leave_id'   => $leaveCreate->id,
                    'description'   => structureApprovalStatusLabel($approvedStatus),
                    'status'        => $approvedStatus,
                ]
            ];
            $dataTracking = [];
            foreach ($data as $dt) {
                $dataTracking[] = LeaveTracking::create($dt);
            }
        }
        return new ApiResponse($this->messageSuccess, $leave ? 200 : 201, 'Cuti Karyawan' . ($leave ? $this->messageUpdated : $this->messageCreated), $leave ? $leave : $leaveCreate);
    }

    public function deleteLeaveByID($id)
    {
        $leave = LeaveApplication::find($id);

        if (empty($leave)) {
            return new ApiResponse($this->messageError, 200, 'Cuti' . $this->messageNotFound);
        }

        $storage = $leave->attachment;
        if ($storage) {
            if(Storage::exists($storage)) Storage::delete($storage);
        }
        $leave->delete();
        return new ApiResponse($this->messageSuccess, 200, 'Cuti' . $this->messageDeleted);
    }

    public function approveLeave(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'leave_id'           => 'required',
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

            // Get leave
            $leave = LeaveApplication::find($request->leave_id);

            if (empty($leave)) {
                return new ApiResponse($this->messageError, 200, 'Data Cuti' . $this->messageNotFound);
            }

            // Get Employee
            $employee = $this->getEmployee($leave->employee_id);

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

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti belum pada tahap HRD');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Disetujui Spv HRD";
                    $leaveTracking->status = $approvedStatus;
                    $leaveTracking->datetime = $request->approved_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 3;

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti belum pada tahap HRD');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Disetujui Manager HRD";
                    $leaveTracking->status = $approvedStatus;
                    $leaveTracking->datetime = $request->approved_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 2 && $deptId != 9) {
                $approvedStatus = 0;

                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $newApprovedStatus = 1;
                }

                if ($employee['department_id'] != $deptId) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti bukan dari departemen yang sama !');
                }

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti belum pada tahap Manager');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Disetujui Manager";
                    $leaveTracking->status = $approvedStatus;
                    $leaveTracking->datetime = $request->approved_date;
                    $leaveTracking->save();
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

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti belum pada tahap GM');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Disetujui GM";
                    $leaveTracking->status = $approvedStatus;
                    $leaveTracking->datetime = $request->approved_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 4;

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti belum pada tahap Direktur');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Disetujui Direktur";
                    $leaveTracking->status = $approvedStatus;
                    $leaveTracking->datetime = $request->approved_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 5;

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan cuti belum pada tahap Komisaris');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Disetujui Komisaris";
                    $leaveTracking->status = $approvedStatus;
                    $leaveTracking->datetime = $request->approved_date;
                    $leaveTracking->save();
                }
            } else {
                return new ApiResponse($this->messageError, 403, 'Anda tidak memiliki akses untuk menyetujui cuti !');
            }

            $trackingData = [];

            //=============================
            // cek data karyawan ada atau tidak

            if ($newApprovedStatus == 1) {
                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $trackingData = [
                        'leave_id'       => $leave->id,
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
                            'leave_id'       => $leave->id,
                            'description'   => 'Diperiksa HRD',
                            'status'        => $newApprovedStatus,
                            'datetime'      => $request->approved_date
                        ];
                    }
                } else {
                    $trackingData = [
                        'leave_id'       => $leave->id,
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
                        'leave_id'   => $leave->id,
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
                        'leave_id'   => $leave->id,
                        'description'   => 'Diperiksa Komisaris',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if ($newApprovedStatus == 5) {
                $trackingData = [
                    'leave_id'   => $leave->id,
                    'description'   => 'Disetujui',
                    'status'        => $newApprovedStatus,
                    'datetime'      => $request->approved_date
                ];
            }
            //================================

            // Create leave tracking
            if (!empty($trackingData)) {
                leaveTracking::create($trackingData);
            }

            // Update leave
            $leave->approved_status = $newApprovedStatus;
            $leave->is_read = 0;
            $leave->save();

            return new ApiResponse($this->messageSuccess, 200, 'Cuti Karyawan' . $this->messageUpdated, LeaveApplication::with(['tracking'])->where('id', $leave->id)->first());
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }

    public function rejectLeave(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'leave_id'       => 'required',
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
            // Get leave
            $leave = LeaveApplication::find($request->leave_id);

            if (empty($leave)) {
                return new ApiResponse($this->messageError, 200, 'Data Cuti' . $this->messageNotFound);
            }

            // Get Employee
            $employee = $this->getEmployee($leave->employee_id);

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

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti belum pada tahap HRD');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Ditolak Spv HRD";
                    $leaveTracking->description_rejected = $request->reject_statement;
                    $leaveTracking->status = $newApprovedStatus;
                    $leaveTracking->datetime = $request->reject_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 8;

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti belum pada tahap HRD');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Ditolak Manager HRD";
                    $leaveTracking->description_rejected = $request->reject_statement;
                    $leaveTracking->status = $newApprovedStatus;
                    $leaveTracking->datetime = $request->reject_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 2 && $deptId != 9) {
                if ($employee['department_id'] != $deptId) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti bukan dari departemen yang sama!');
                }

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti belum pada tahap Manager');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Ditolak Manager";
                    $leaveTracking->description_rejected = $request->reject_statement;
                    $leaveTracking->status = $newApprovedStatus;
                    $leaveTracking->datetime = $request->reject_date;
                    $leaveTracking->save();
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

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti belum pada tahap GM');
                }

                // Get leave tracking
                $leaveTracking = LeaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Ditolak GM";
                    $leaveTracking->description_rejected = $request->reject_statement;
                    $leaveTracking->status = $newApprovedStatus;
                    $leaveTracking->datetime = $request->reject_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 9;

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti belum pada tahap Direktur');
                }

                // Get leave tracking
                $leaveTracking = leaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Ditolak Direktur";
                    $leaveTracking->description_rejected = $request->reject_statement;
                    $leaveTracking->status = $newApprovedStatus;
                    $leaveTracking->datetime = $request->reject_date;
                    $leaveTracking->save();
                }
            } else if ($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 10;

                if ($leave->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan Cuti belum pada tahap Komisaris');
                }

                // Get leave tracking
                $leaveTracking = leaveTracking::where('leave_id', $leave->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($leaveTracking)) {
                    $leaveTracking->description = "Ditolak Komisaris";
                    $leaveTracking->description_rejected = $request->reject_statement;
                    $leaveTracking->status = $newApprovedStatus;
                    $leaveTracking->datetime = $request->reject_date;
                    $leaveTracking->save();
                }
            } else {
                return new ApiResponse($this->messageError, 403, 'Anda tidak memiliki akses untuk menolak Cuti');
            }
            // Update leave
            $leave->approved_status = $newApprovedStatus;
            $leave->is_read = 0;
            $leave->save();
            return new ApiResponse($this->messageSuccess, 200, 'Cuti karyawan berhasil ditolak !', LeaveApplication::with(['tracking'])->where('id', $leave->id)->first());
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResponse;
use App\Models\SickApplication;
use App\Models\SickTracking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;


class SickController extends BaseController
{
    public function getAllSick()
    {
        $sick = SickApplication::all();
        if ($sick->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Sakit' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $sick);
    }

    public function getSickByID($id)
    {
        $sick = SickApplication::find($id);
        if ($sick === null) {
            return new ApiResponse($this->messageError, 200, 'Sakit' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $sick);
    }

    public function getSickByEmployeeID($id)
    {
        $sick = SickApplication::where('employee_id', $id)->get();
        if ($sick->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Sakit' . $this->messageNotFound);
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $sick);
    }

    public function storeSick(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'employee_create_id' => 'required',
            'sick_start_date' => 'required|date_format:Y-m-d',
            'sick_end_date' => 'required|date_format:Y-m-d',
            'description' => 'required|string',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5048',
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
        $sickStartDate = Carbon::parse($request->sick_start_date);
        $sickEndDate = Carbon::parse($request->sick_end_date);

        $existingSickLeaves = SickApplication::where('employee_id', $employeeId)
            ->where(function ($query) use ($sickStartDate, $sickEndDate) {
                $query->whereBetween('sick_start_date', [$sickStartDate, $sickEndDate])
                    ->orWhereBetween('sick_end_date', [$sickStartDate, $sickEndDate])
                    ->orWhere(function ($query) use ($sickStartDate, $sickEndDate) {
                        $query->where('sick_start_date', '<=', $sickStartDate)
                            ->where('sick_end_date', '>=', $sickEndDate);
                    });
            })
            ->exists();

        if ($existingSickLeaves) {
            return new ApiResponse($this->messageError, 400, 'Jarak Tanggal awal dan akhir sakit sudah ada !');
        }

        return $this->saveSick($request);
    }

    public function updateSick(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'employee_create_id' => 'required',
            'sick_start_date' => 'required|date_format:Y-m-d',
            'sick_end_date' => 'required|date_format:Y-m-d',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5048',
        ], [
            'date_format' => 'Format :attribute harus :format.',
            'file' => 'Field :attribute harus berupa file.',
            'mimes' => 'Field :attribute harus berupa file dengan tipe: :values.',
            'max' => 'Field :attribute tidak boleh lebih dari :max kilobytes.',
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $sick = SickApplication::find($id);
        if (!$sick) {
            return new ApiResponse($this->messageError, 200, 'Data sakit' . $this->messageNotFound);
        }

        return $this->saveSick($request, $sick);
    }

    public function getAllSickByApprover($approverId)
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
            $sicks = [];
            if ($roleId == 1 && $deptId == 9 && $jobTitleId == 34) {
                $sicks = SickApplication::where('approved_status', 2)
                ->orderBy('is_read')
                ->get();
                if (count($sicks) < 1) $sicks = [];
            } else if (
                $roleId == 2 && $deptId == 9
            ) {
                $sicks = SickApplication::where('approved_status', 2)
                ->orderBy('is_read')
                ->get();
                if (count($sicks) < 1) $sicks = [];
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

                // get sick data
                foreach ($employees as $employee) {
                    $employeeSicks = SickApplication::where('approved_status', 0)
                    ->where('employee_id', $employee['id'])
                    ->orderBy('is_read')
                    ->get();

                    if ($employeeSicks->isNotEmpty()) {
                        foreach ($employeeSicks as $sick) {
                            $sicks[] = $sick;
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
                    $employeeSicks = SickApplication::where('approved_status', 1)
                    ->where('employee_id', $employee['id'])
                    ->orderBy('is_read')
                    ->get();

                    if ($employeeSicks->isNotEmpty()) {
                        foreach ($employeeSicks as $sick) {
                            $sicks[] = $sick;
                        }
                    }
                }
            } else if ($roleId == 4) {
                $sicks = SickApplication::where('approved_status', 3)
                ->orderBy('is_read')
                ->get();

                if (count($sicks) < 1) $sicks = [];
            } else if (
                $roleId == 5
            ) {
                $sicks = SickApplication::where('approved_status', 4)
                ->orderBy('is_read')
                ->get();

                if (count($sicks) < 1) $sicks = [];
            } else {
                return new ApiResponse($this->messageError, 403, 'Tidak bisa mengakses daftar sakit karyawan lain!');
            }

            if (empty($sicks)) {
                return new ApiResponse($this->messageError, 200, 'Data sakit' . $this->messageNotFound);
            }

            return new ApiResponse($this->messageSuccess, 200, 'Ok', $sicks);
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }

    private function saveSick(Request $request, $sick = null)
    {
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

        $sickStartDate = Carbon::parse($request->sick_start_date);
        $sickEndDate = $request->sick_end_date ? Carbon::parse($request->sick_end_date) : $sickStartDate;

        if ($sickEndDate->lt($sickStartDate)) {
            return new ApiResponse($this->messageError, 400, 'Tanggal akhir tidak boleh kurang dari tanggal awal !');
        }

        $distribution =  distributionPermitDay($sickStartDate, $sickEndDate);

        $totalFirstMonth = $distribution['firstMonth'];
        $totalSecondMonth = $distribution['secondMonth'];

        $totalDays = $sickStartDate->eq($sickEndDate) ? 1 : $totalFirstMonth + $totalSecondMonth;

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
            'employee_create_id' => $request->employee_create_id,
            'sick_start_date' => $request->sick_start_date,
            'sick_end_date' => $request->sick_end_date,
            'description' => $request->description,
            'total_day' => $totalDays,
            'total_first_month' => $totalFirstMonth,
            'total_second_month' => $totalSecondMonth,
            'sick_branch' => $branchCode,
            'approved_status' => $approvedStatus,
        ];

        if ($attachment) {
            $attachmentName = Str::uuid() . '-' . $attachment->getClientOriginalName();
            $path = "sick/employee/$employeeId/$attachmentName";
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            $pathUpload = $attachment->storeAs("sick/employee/$employeeId", $attachmentName);
            $data['attachment'] = $pathUpload;
        }

        if ($sick) {
            if ($attachment) {
                $storage = $sick->attachment;
                if ($storage) {
                    if(Storage::exists($storage)) Storage::delete($storage);
                }
            } else {
                $data['attachment'] = $sick->attachment;
            }
            $sick->update($data);
        } else {
            $sickCreate = SickApplication::create($data);
            $sickCreate->approved_status = $approvedStatus;
            $sickCreate->save();
            $submitDate = Carbon::now()->format('Y-m-d H:i:s');
            $data = [
                [
                    'sick_id'   => $sickCreate->id,
                    'description'   => 'Sakit berhasil diajukan',
                    'datetime'      => $submitDate
                ],
                [
                    'sick_id'   => $sickCreate->id,
                    'description'   => structureApprovalStatusLabel($approvedStatus),
                    'status'        => $approvedStatus,
                ]
            ];
            $dataTracking = [];
            foreach ($data as $dt) {
                $dataTracking[] = SickTracking::create($dt);
            }
        }
        return new ApiResponse($this->messageSuccess, $sick ? 200 : 201, 'Sakit Karyawan' . ($sick ? $this->messageUpdated : $this->messageCreated), $sick ? $sick : $sickCreate);
    }

    public function deleteSickByID($id)
    {
        $sick = SickApplication::find($id);

        if ($sick === null) {
            return new ApiResponse($this->messageError, 200, 'Sakit' . $this->messageNotFound);
        }

        $storage = $sick->attachment;

        if ($storage) {
            if(Storage::exists($storage)) Storage::delete($storage);
        }

        $sick->delete();
        return new ApiResponse($this->messageSuccess, 200, 'Sakit' . $this->messageDeleted);
    }

    public function approveSick(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sick_id'           => 'required',
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

            // Get sick
            $sick = SickApplication::find($request->sick_id);

            if (empty($sick)) {
                return new ApiResponse($this->messageError, 200, 'Data sakit' . $this->messageNotFound);
            }

            // Get Employee
            $employee = $this->getEmployee($sick->employee_id);

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

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap HRD');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Disetujui Spv HRD";
                    $sickTracking->status = $approvedStatus;
                    $sickTracking->datetime = $request->approved_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 3;

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap HRD');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Disetujui Manager HRD";
                    $sickTracking->status = $approvedStatus;
                    $sickTracking->datetime = $request->approved_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 2 && $deptId != 9) {
                $approvedStatus = 0;

                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $newApprovedStatus = 1;
                }

                if ($employee['department_id'] != $deptId) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit bukan dari departemen yang sama !');
                }

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap Manager');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Disetujui Manager";
                    $sickTracking->status = $approvedStatus;
                    $sickTracking->datetime = $request->approved_date;
                    $sickTracking->save();
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

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap GM');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Disetujui GM";
                    $sickTracking->status = $approvedStatus;
                    $sickTracking->datetime = $request->approved_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 4;

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap Direktur');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Disetujui Direktur";
                    $sickTracking->status = $approvedStatus;
                    $sickTracking->datetime = $request->approved_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 5;

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap Komisaris');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Disetujui Komisaris";
                    $sickTracking->status = $approvedStatus;
                    $sickTracking->datetime = $request->approved_date;
                    $sickTracking->save();
                }
            } else {
                return new ApiResponse($this->messageError, 403, 'Anda tidak memiliki akses untuk menyetujui sakit !');
            }

            $trackingData = [];

            //=============================
            // cek data karyawan ada atau tidak
            if ($newApprovedStatus == 1) {
                if (empty($gm)) {
                    $newApprovedStatus = 2;
                } else {
                    $trackingData = [
                        'sick_id'       => $sick->id,
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
                            'sick_id'       => $sick->id,
                            'description'   => 'Diperiksa HRD',
                            'status'        => $newApprovedStatus,
                            'datetime'      => $request->approved_date
                        ];
                    }
                } else {
                    $trackingData = [
                        'sick_id'       => $sick->id,
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
                        'sick_id'   => $sick->id,
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
                        'sick_id'   => $sick->id,
                        'description'   => 'Diperiksa Komisaris',
                        'status'        => $newApprovedStatus,
                        'datetime'      => $request->approved_date
                    ];
                }
            }

            if ($newApprovedStatus == 5) {
                $trackingData = [
                    'sick_id'   => $sick->id,
                    'description'   => 'Disetujui',
                    'status'        => $newApprovedStatus,
                    'datetime'      => $request->approved_date
                ];
            }
            //================================

            // Create sick tracking
            if (!empty($trackingData)) {
                SickTracking::create($trackingData);
            }

            // Update sick
            $sick->approved_status = $newApprovedStatus;
            $sick->is_read = 0;
            $sick->save();

            return new ApiResponse($this->messageSuccess, 200, 'Sakit karyawan berhasil diterima !', SickApplication::with(['tracking'])->where('id', $sick->id)->first());
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }

    public function rejectSick(Request $request,)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sick_id'       => 'required',
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

            // Get sick
            $sick = SickApplication::find($request->sick_id);

            if (empty($sick)) {
                return new ApiResponse($this->messageError, 200, 'Data sakit' . $this->messageNotFound);
            }

            // Get Employee
            $employee = $this->getEmployee($sick->employee_id);

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

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap HRD');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Ditolak Spv HRD";
                    $sickTracking->description_rejected = $request->reject_statement;
                    $sickTracking->status = $newApprovedStatus;
                    $sickTracking->datetime = $request->reject_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 2 && $deptId == 9) {
                $approvedStatus = 2;
                $newApprovedStatus = 8;

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap HRD');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Ditolak Manager HRD";
                    $sickTracking->description_rejected = $request->reject_statement;
                    $sickTracking->status = $newApprovedStatus;
                    $sickTracking->datetime = $request->reject_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 2 && $deptId != 9) {
                if ($employee['department_id'] != $deptId) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit bukan dari departemen yang sama!');
                }

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap Manager');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Ditolak Manager";
                    $sickTracking->description_rejected = $request->reject_statement;
                    $sickTracking->status = $newApprovedStatus;
                    $sickTracking->datetime = $request->reject_date;
                    $sickTracking->save();
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

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap GM');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Ditolak GM";
                    $sickTracking->description_rejected = $request->reject_statement;
                    $sickTracking->status = $newApprovedStatus;
                    $sickTracking->datetime = $request->reject_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 4) {
                $approvedStatus = 3;
                $newApprovedStatus = 9;

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap Direktur');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Ditolak Direktur";
                    $sickTracking->description_rejected = $request->reject_statement;
                    $sickTracking->status = $newApprovedStatus;
                    $sickTracking->datetime = $request->reject_date;
                    $sickTracking->save();
                }
            } else if ($roleId == 5) {
                $approvedStatus = 4;
                $newApprovedStatus = 10;

                if ($sick->approved_status != $approvedStatus) {
                    return new ApiResponse($this->messageError, 403, 'Pengajuan sakit belum pada tahap Komisaris!');
                }

                // Get sick tracking
                $sickTracking = SickTracking::where('sick_id', $sick->id)
                    ->where('status', $approvedStatus)
                    ->first();

                if (!empty($sickTracking)) {
                    $sickTracking->description = "Ditolak Komisaris";
                    $sickTracking->description_rejected = $request->reject_statement;
                    $sickTracking->status = $newApprovedStatus;
                    $sickTracking->datetime = $request->reject_date;
                    $sickTracking->save();
                }
            } else {
                return new ApiResponse($this->messageError, 403, 'Anda tidak memiliki akses untuk menolak sakit!');
            }

            // Update sick
            $sick->approved_status = $newApprovedStatus;
            $sick->is_read = 0;
            $sick->save();
            return new ApiResponse($this->messageSuccess, 200, 'Sakit karyawan berhasil ditolak !', SickApplication::with(['tracking'])->where('id', $sick->id)->first());
        } catch (Exception $e) {
            return new ApiResponse($this->messageError, 500, $e->getMessage());
        }
    }
}

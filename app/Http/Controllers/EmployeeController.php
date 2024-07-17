<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\ContractJobdesk;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBank;
use App\Models\EmployeeBiodata;
use App\Models\EmployeeChild;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamily;
use App\Models\EmployeeSibling;
use App\Models\EmployeeSkill;
use App\Models\EmployeeWorkHour;
use App\Models\JobTitle;
use App\Models\Skill;
use App\Models\WorkHour;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    private $api;
    private $apiMail;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_LETTER');
        $this->apiMail = env('URL_SERVICE_MAIL');
        $this->client = new Client();
    }

    // GET ALL EMPLOYEE
    public function index(Request $request)
    {
        try {
            $employee = Employee::with(['contract', 'biodata', 'education', 'family', 'document', 'jobTitle', 'department', 'workHour', 'branch', 'bank']);

            if($request->has('role_id')) {
                $roleId = $request->query('role_id');
                if(!empty($roleId)) $employee->where('role_id', $roleId);
            } else {
                $employee->whereNot('role_id', 6);
            }

            if($request->has('department_code')) {
                $departmentCode = $request->query('department_code');

                if(!empty($departmentCode)) {

                    $department = Department::where('department_code', $departmentCode)->first();

                    if(!empty($department)) {
                        $employee->where('department_id', $department->id);
                    } else {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 204,
                            'message'   => 'Employee not found',
                            'data'      => []
                        ], 200);
                    }

                }
            }

            if($request->has('branch_code')) {
                $branchCode = $request->query('branch_code');

                if(!empty($branchCode)) {

                    $branch = Branch::where('branch_code', $branchCode)->first();

                    if(!empty($branch)) {
                        $employee->where('branch_code', $branch->branch_code);
                    } else {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 204,
                            'message'   => 'Employee not found',
                            'data'      => []
                        ], 200);
                    }

                }
            }

            if($request->has('job_title')) {
                $jobTitleId = $request->query('job_title');

                if(!empty($jobTitleId)) {

                    $jobTitle = JobTitle::where('id', $jobTitleId)->first();

                    if(!empty($jobTitle)) {
                        $employee->where('job_title_id', $jobTitle->id);
                    } else {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 204,
                            'message'   => 'Employee not found',
                            'data'      => []
                        ], 200);
                    }

                }
            }

            if($request->has('status')) {
                $status = $request->query('status');
                if(!empty($status) || $status == 0) $employee->where('status', $status);
            }

            $employee = $employee->get();

            if(count($employee) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Employee not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // GET BY ID
    public function getEmployeeById($id = null)
    {
        try {
            $employee = Employee::with(['contract', 'biodata', 'education', 'family', 'document', 'jobTitle', 'department', 'workHour', 'branch', 'bank'])
                            ->where('id', $id)
                            ->first();

            if(empty($id) || empty($employee)) {
                return response()->json([
                    'status'        => 'error',
                    'code'          => 204,
                    'message'       => 'Employee not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // Get Karyawan Not Absent

    public function getNotAbsentEmployee(Request $request)
    {
        try {
            if (!$request->has('date')) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Date is required'
                ], 400);
            }

            if (!$request->has('employee_id')) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Employee ID is required'
                ], 400);
            }

            $employeeIds = $request->query('employee_id', []);
            if (!is_array($employeeIds)) {
                $employeeIds = explode(',', $employeeIds);
            }

            $employeeNotAbsent = Employee::where('status', 3)
                ->where('is_daily', 0)
                ->whereNotIn('role_id', [4, 5, 6])
                ->whereNotIn('id', $employeeIds)
                ->where('created_at', '<=', $request->query('date'))
                ->get();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employeeNotAbsent
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    /*================================
                VERIFICATION
    ================================*/

    // REGISTER
    public function verifyRegister(Request $request)
    {
        try {
            // Check Validation
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'nik'               => 'required|unique:employees,nik',
                'employee_status'   => 'required',
                'salary'            => 'required|numeric|max:999999999',
                'show_contract'     => 'required',
                'start_work'        => 'required|date'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            if($request->employee_status != 'pkwt' && $request->employee_status != 'project' && $request->employee_status != 'probation' && $request->employee_status != 'permanent' && $request->employee_status != 'daily') {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Employee status options are permanent, probation, pkwt, and daily'
                ], 400);
            }

            // get employee
            $employee = Employee::find($request->employee_id);

            // employee not found
            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => '204',
                    'message'   => 'Employee not found'
                ], 200);
            }

            // employee register is verified
            if($employee->status > 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Employee registration has been verified'
                ], 400);
            }

            //========================
                /* LETTER NUMBER */
            //========================

            $contractLetterNumber = null;
            $statementLetterNumber = null;
            $contractLetterCode = 'SPK.K';
            $statementLetterCode = 'SPT';

            // Contract Show
            if($request->show_contract == true || $request->show_contract == 1) {
                $rules = [];
                // contract status pkwt || probation
                if($request->employee_status == 'pkwt' || $request->employee_status == 'probation') {
                    $rules = [
                        'contract_length_num'   => 'required',
                        'contract_length_time'  => 'required',
                        'start_contract'        => 'required',
                        'end_contract'          => 'required',
                    ];

                    $contractTime = $request->contract_length_time;

                    if($contractTime != 'hari' && $contractTime != 'minggu' && $contractTime != 'bulan' && $contractTime != 'tahun') {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 400,
                            'message'   => 'Contract length time options are hari, minggu, bulan, and tahun'
                        ], 400);
                    }
                }

                // contract status project
                if($request->employee_status == 'project') {
                    $rules = [
                        'project'               => 'required',
                        'start_contract'        => 'required',
                    ];
                }

                // contract status daily
                if($request->employee_status == 'daily') {
                    $rules = [
                        'start_contract'        => 'required',
                    ];
                }

                //validate
                $validator = Validator::make($request->all(), $rules);

                if($validator->fails()) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => $validator->errors()
                    ], 400);
                }

                // contract letter
                try {
                    // get new number
                    $responseData = $this->client->get("$this->api/new-number/company/$contractLetterCode");
                    $statusCode = $responseData->getStatusCode();
                    $body = $responseData->getBody()->getContents();

                    $response = json_decode($body, true);

                    // response status check
                    if($response['status'] == 'error') return response()->json($response, $statusCode);

                    $contractLetterNumber = $response['data'];

                    // create contract letter
                    $contractLetterData = [
                        'employee_receiving_id'     => $request->employee_id,
                        'category_code'             => $contractLetterCode,
                        'letter_number'             => $contractLetterNumber,
                        'subject'                   => "Kontrak Kerja $employee->fullname"
                    ];

                    $this->client->post("$this->api", [
                        'json'  => $contractLetterData
                    ]);

                } catch (ClientException $e) {
                    $responseData = $e->getResponse();
                    $statusCode = $responseData->getStatusCode();
                    $body = $responseData->getBody()->getContents();
                    $response = json_decode($body);

                    return response()->json([$response], $statusCode);
                }
            }

            // Statement Letter
            try {
                // get new number
                $responseData = $this->client->get("$this->api/new-number/company/$statementLetterCode");
                $statusCode = $responseData->getStatusCode();
                $body = $responseData->getBody()->getContents();

                $response = json_decode($body, true);

                // response status check
                if($response['status'] == 'error') return response()->json($response, $statusCode);

                $statementLetterNumber = $response['data'];

                // create contract letter
                $statementLetterData = [
                    'employee_receiving_id'     => $request->employee_id,
                    'category_code'             => $statementLetterCode,
                    'letter_number'             => $statementLetterNumber,
                    'subject'                   => "Surat Pernyataan $employee->fullname",
                    'description'               => 'Pernyataan saat awal register pada aplikasi HRIS PT. Maha Akbar Sejahtera'
                ];

                $this->client->post("$this->api", [
                    'json'  => $statementLetterData
                ]);

            } catch (ClientException $e) {
                $responseData = $e->getResponse();
                $statusCode = $responseData->getStatusCode();
                $body = $responseData->getBody()->getContents();
                $response = json_decode($body);

                return response()->json([$response], $statusCode);
            }
            //==========================================

            // contract data
            $contractData = [
                'employee_id'           => $request->employee_id,
                'letter_number'         => $contractLetterNumber,
                'job_title_id'          => $employee->job_title_id,
                'department_id'         => $employee->department_id,
                'branch_code'           => $employee->branch_code,
                'contract_status'       => $request->employee_status,
                'salary'                => $request->salary,
                'project'               => $request->project,
                'contract_length_num'   => $request->contract_length_num,
                'contract_length_time'  => $request->contract_length_time,
                'start_contract'        => $request->start_contract,
                'end_contract'          => $request->end_contract,
                'jobdesk_content'       => $request->jobdesk_content,
            ];

            // create contract
            $employeeContract = EmployeeContract::create($contractData);

            // update employee
            $employeeData = [
                'nik'                   => $request->nik,
                'integrity_pact_num'    => $statementLetterNumber,
                'contract_id'           => $employeeContract->id,
                'employee_status'       => $employeeContract->contract_status,
                'salary'                => $employeeContract->salary,
                'show_contract'         => $request->show_contract,
                'status'                => 1
            ];

            Employee::where('id', $request->employee_id)->update($employeeData);

            $employeeBiodata = [
                'start_work'    => $request->start_work
            ];

            $biodata = EmployeeBiodata::updateOrCreate(['employee_id'   => $request->employee_id], $employeeBiodata);

            // Send Mail Verification
            $urlVerification = URL::temporarySignedRoute(
                'mail.verify', now()->addHours(24), ['id' => $employee->id, 'hash' => sha1($employee->getEmailForVerification())]
            );

            $mailData = [
                'email'                 => $employee->email,
                'name'                  => $employee->fullname,
                'url_verification'      => $urlVerification
            ];

            $this->client->post("$this->apiMail/send-mail-verification", [
                'json'  => $mailData
            ]);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => [
                    'employee'  => Employee::find($request->employee_id),
                    'contract'  => $employeeContract
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function rejectRegister(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'           => 'required',
                'statement_rejected'    => 'required'
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
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $employeeStatus = $employee->status;

            if($employeeStatus == 7) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Registrasi karyawan telah ditolak sebelumnya!',
                    'data'      => []
                ], 200);
            }

            if($employeeStatus != 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak pada tahap verifikasi registrasi!',
                    'data'      => []
                ], 200);
            }

            // Update
            $employee->statement_rejected = $request->statement_rejected;
            $employee->status = 7;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // BIODATA
    public function verifyData($employeeId, Request $request)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Status Check
            if($employee->status != 2) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Karyawan tidak pada tahap verifikasi data oleh HR Recruitment',
                    'data'      => []
                ], 403);
            }

            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_letter_code'  => 'required|unique:employees,employee_letter_code'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Update
            $employee->employee_letter_code = $request->employee_letter_code;
            $employee->status = 9;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function verifyDataPhaseTwo($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Status Check
            if($employee->status != 9) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Karyawan tidak pada tahap verifikasi data oleh HRD Manager',
                    'data'      => []
                ], 403);
            }

            // Update
            $employee->status = ($employee->show_contract == 1) ? 6 : 3;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function rejectData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'           => 'required',
                'statement_rejected'    => 'required'
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
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $employeeStatus = $employee->status;

            if($employeeStatus == 8) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Data karyawan telah ditolak sebelumnya!',
                    'data'      => []
                ], 200);
            }

            if($employeeStatus != 2) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak pada tahap verifikasi data!',
                    'data'      => []
                ], 200);
            }

            // Update
            $employee->statement_rejected = $request->statement_rejected;
            $employee->status = 8;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function rejectDataPhaseTwo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'           => 'required',
                'statement_rejected'    => 'required'
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
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $employeeStatus = $employee->status;

            if($employeeStatus == 11) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Data karyawan telah ditolak sebelumnya!',
                    'data'      => []
                ], 200);
            }

            if($employeeStatus != 9) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak pada tahap verifikasi data HR Manager!',
                    'data'      => []
                ], 200);
            }

            // Update
            $employee->statement_rejected = $request->statement_rejected;
            $employee->status = 11;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // ========================================

    /*==========================
            BIODATA
    ==========================*/
    // BIODATA
    public function getEmployeeBiodata($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Biodata
            $biodata = EmployeeBiodata::where('employee_id', $employeeId)->first();

            if(empty($biodata)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Biodata karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $biodata
            ], 200);

        } catch(Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeEmployeeBiodata(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'               => 'required',
                'fullname'                  => 'required',
                'nickname'                  => 'required',
                'nik'                       => 'required|digits:16',
                'identity_province'         => 'required|numeric',
                'identity_regency'          => 'required|numeric',
                'identity_district'         => 'required|numeric',
                'identity_village'          => 'required|numeric',
                // 'identity_postal_code'      => 'required|numeric:digits:5',
                'identity_address'          => 'required',
                'current_province'          => 'required|numeric',
                'current_regency'           => 'required|numeric',
                'current_district'          => 'required|numeric',
                'current_village'           => 'required|numeric',
                // 'current_postal_code'       => 'required|numeric:digits:5',
                'current_address'           => 'required',
                'residence_status'          => 'required',
                'phone_number'              => 'required|numeric',
                'emergency_phone_number'    => 'required|numeric',
                'gender'                    => 'required|in:L,P',
                'birth_place'               => 'required',
                'birth_date'                => 'required|date',
                'religion'                  => 'required|in:islam,kristen,buddha,hindu,konghucu',
                // 'blood_type'                => 'required|in:A,B,AB,O',
                // 'weight'                    => 'required|numeric',
                // 'height'                    => 'required|numeric',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            if($request->phone_number == $request->emergency_phone_number) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Nomor HP dan kontak darurat tidak boleh sama'
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Get Biodatas
            $employeeBiodata = EmployeeBiodata::where('employee_id', $request->employee_id)->first();

            if(!empty($employeeBiodata)) {
                $rules = [];

                if($employeeBiodata->nik != $request->nik) $rules['nik'] = 'unique:employee_biodatas,nik';
                if($employeeBiodata->phone_number != $request->phone_number) $rules['phone_number'] = 'unique:employee_biodatas,phone_number';

                if(!empty($rules)) {
                    $validator = Validator::make($request->all(), $rules);

                    if($validator->fails()) {
                        return response()->json([
                            'status'    => 'error',
                            'code'      => 400,
                            'message'   => $validator->errors()
                        ], 400);
                    }
                }
            }

            // store
            $data = [
                'employee_id'               => $request->employee_id,
                'fullname'                  => $request->fullname,
                'nickname'                  => $request->nickname,
                'nik'                       => $request->nik,
                'identity_province'         => $request->identity_province,
                'identity_regency'          => $request->identity_regency,
                'identity_district'         => $request->identity_district,
                'identity_village'          => $request->identity_village,
                'identity_postal_code'      => $request->identity_postal_code,
                'identity_address'          => $request->identity_address,
                'current_province'          => $request->current_province,
                'current_regency'           => $request->current_regency,
                'current_district'          => $request->current_district,
                'current_village'           => $request->current_village,
                'current_postal_code'       => $request->current_postal_code,
                'current_address'           => $request->current_address,
                'residence_status'          => $request->residence_status,
                'phone_number'              => $request->phone_number,
                'emergency_phone_number'    => $request->emergency_phone_number,
                'gender'                    => $request->gender,
                'birth_place'               => $request->birth_place,
                'birth_date'                => $request->birth_date,
                'religion'                  => $request->religion,
                'blood_type'                => $request->blood_type,
                'weight'                    => $request->weight,
                'height'                    => $request->height,
            ];

            $biodata = EmployeeBiodata::updateOrCreate(['employee_id' => $request->employee_id], $data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $biodata
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // EDUCATION
    public function getEmployeeEducation($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Education
            $education = EmployeeEducation::where('employee_id', $employeeId)->first();

            $educationData = [];

            if($education->last_education == 'sd') {
                $educationData = [
                    'employee_id'           => $employeeId,
                    'last_education'        => $education->last_education,
                    'primary_school'        => $education->primary_school,
                    'ps_start_year'         => $education->ps_start_year,
                    'ps_end_year'           => $education->ps_end_year,
                ];
            }

            if($education->last_education == 'smp') {
                $educationData = [
                    'employee_id'           => $employeeId,
                    'last_education'        => $education->last_education,
                    'junior_high_school'    => $education->junior_high_school,
                    'jhs_start_year'        => $education->jhs_start_year,
                    'jhs_end_year'          => $education->jhs_end_year,
                ];
            }

            if($education->last_education == 'sma') {
                $educationData = [
                    'employee_id'           => $employeeId,
                    'last_education'        => $education->last_education,
                    'senior_high_school'    => $education->senior_high_school,
                    'shs_start_year'        => $education->shs_start_year,
                    'shs_end_year'          => $education->shs_end_year,
                ];
            }

            if($education->last_education == 'd i' || $education->last_education == 'd ii' || $education->last_education == 'd iii' || $education->last_education == 's1') {
                $educationData = [
                    'employee_id'           => $employeeId,
                    'last_education'        => $education->last_education,
                    'senior_high_school'    => $education->senior_high_school,
                    'shs_start_year'        => $education->shs_start_year,
                    'shs_end_year'          => $education->shs_end_year,
                    'bachelor_university'   => $education->bachelor_university,
                    'bachelor_major'        => $education->bachelor_major,
                    'bachelor_start_year'   => $education->bachelor_start_year,
                    'bachelor_end_year'     => $education->bachelor_end_year,
                    'bachelor_gpa'          => $education->bachelor_gpa,
                    'bachelor_degree'       => $education->bachelor_degree,
                ];
            }

            if($education->last_education == 's2') {
                $educationData = [
                    'employee_id'           => $employeeId,
                    'last_education'        => $education->last_education,
                    'senior_high_school'    => $education->senior_high_school,
                    'shs_start_year'        => $education->shs_start_year,
                    'shs_end_year'          => $education->shs_end_year,
                    'bachelor_university'   => $education->bachelor_university,
                    'bachelor_major'        => $education->bachelor_major,
                    'bachelor_start_year'   => $education->bachelor_start_year,
                    'bachelor_end_year'     => $education->bachelor_end_year,
                    'bachelor_gpa'          => $education->bachelor_gpa,
                    'bachelor_degree'       => $education->bachelor_degree,
                    'master_university'     => $education->master_university,
                    'master_major'          => $education->master_major,
                    'master_start_year'     => $education->master_start_year,
                    'master_end_year'       => $education->master_end_year,
                    'master_gpa'            => $education->master_gpa,
                    'master_degree'         => $education->master_degree
                ];
            }

            if($education->last_education == 's3') {
                $educationData = [
                    'employee_id'           => $employeeId,
                    'last_education'        => $education->last_education,
                    'senior_high_school'    => $education->senior_high_school,
                    'shs_start_year'        => $education->shs_start_year,
                    'shs_end_year'          => $education->shs_end_year,
                    'bachelor_university'   => $education->bachelor_university,
                    'bachelor_major'        => $education->bachelor_major,
                    'bachelor_start_year'   => $education->bachelor_start_year,
                    'bachelor_end_year'     => $education->bachelor_end_year,
                    'bachelor_gpa'          => $education->bachelor_gpa,
                    'bachelor_degree'       => $education->bachelor_degree,
                    'master_university'     => $education->master_university,
                    'master_major'          => $education->master_major,
                    'master_start_year'     => $education->master_start_year,
                    'master_end_year'       => $education->master_end_year,
                    'master_gpa'            => $education->master_gpa,
                    'master_degree'         => $education->master_degree,
                    'doctoral_university'   => $education->doctoral_university,
                    'doctoral_major'        => $education->doctoral_major,
                    'doctoral_start_year'   => $education->doctoral_start_year,
                    'doctoral_end_year'     => $education->doctoral_end_year,
                    'doctoral_gpa'          => $education->doctoral_gpa,
                    'doctoral_degree'       => $education->doctoral_degree,
                ];
            }

            if(empty($education)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Riwayat pendidikan karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $educationData
            ], 200);

        } catch(Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeEmployeeEducation(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'           => 'required',
                'last_education'        => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Rules Check
            $lastEducation = $request->last_education;

            if($lastEducation != 'sd' && $lastEducation != 'smp' && $lastEducation != 'sma' && $lastEducation != 'd i' && $lastEducation != 'd ii' && $lastEducation != 'd iii' && $lastEducation != 's1' && $lastEducation != 's2' && $lastEducation != 's3') {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Pilihan last education yaitu sd, smp, sma, d i, d ii, d iii, s1, s2, dan s3'
                ], 400);
            }

            $rules = [];

            if($lastEducation == 'sd') {
                $rules = [
                    'primary_school'    => 'required',
                    'ps_start_year'     => 'required|numeric|digits:4',
                    'ps_end_year'       => 'required|numeric|digits:4'
                ];
            }

            if($lastEducation == 'smp') {
                $rules = [
                    'junior_high_school'    => 'required',
                    'jhs_start_year'        => 'required|numeric|digits:4',
                    'jhs_end_year'          => 'required|numeric|digits:4'
                ];
            }

            if($lastEducation == 'sma') {
                $rules = [
                    'senior_high_school'    => 'required',
                    'shs_start_year'        => 'required|numeric|digits:4',
                    'shs_end_year'          => 'required|numeric|digits:4'
                ];
            }

            if($lastEducation == 'd i' || $lastEducation == 'd ii' || $lastEducation == 'd iii' || $lastEducation == 's1') {
                $rules = [
                    'senior_high_school'    => 'required',
                    'shs_start_year'        => 'required|numeric|digits:4',
                    'shs_end_year'          => 'required|numeric|digits:4',
                    'bachelor_university'   => 'required',
                    'bachelor_major'        => 'required',
                    'bachelor_start_year'   => 'required|numeric|digits:4',
                    'bachelor_end_year'     => 'required|numeric|digits:4',
                    'bachelor_gpa'          => 'required',
                    'bachelor_degree'       => 'required'
                ];
            }

            if($lastEducation == 's2') {
                $rules = [
                    'senior_high_school'    => 'required',
                    'shs_start_year'        => 'required|numeric|digits:4',
                    'shs_end_year'          => 'required|numeric|digits:4',
                    'bachelor_university'   => 'required',
                    'bachelor_major'        => 'required',
                    'bachelor_start_year'   => 'required|numeric|digits:4',
                    'bachelor_end_year'     => 'required|numeric|digits:4',
                    'bachelor_gpa'          => 'required',
                    'bachelor_degree'       => 'required',
                    'master_university'     => 'required',
                    'master_major'          => 'required',
                    'master_start_year'     => 'required|numeric|digits:4',
                    'master_end_year'       => 'required|numeric|digits:4',
                    'master_gpa'            => 'required',
                    'master_degree'         => 'required',
                ];
            }

            if($lastEducation == 's3') {
                $rules = [
                    'senior_high_school'    => 'required',
                    'shs_start_year'        => 'required|numeric|digits:4',
                    'shs_end_year'          => 'required|numeric|digits:4',
                    'bachelor_university'   => 'required',
                    'bachelor_major'        => 'required',
                    'bachelor_start_year'   => 'required|numeric|digits:4',
                    'bachelor_end_year'     => 'required|numeric|digits:4',
                    'bachelor_gpa'          => 'required',
                    'bachelor_degree'       => 'required',
                    'master_university'     => 'required',
                    'master_major'          => 'required',
                    'master_start_year'     => 'required|numeric|digits:4',
                    'master_end_year'       => 'required|numeric|digits:4',
                    'master_gpa'            => 'required',
                    'master_degree'         => 'required',
                    'doctoral_university'   => 'required',
                    'doctoral_major'        => 'required',
                    'doctoral_start_year'   => 'required|numeric|digits:4',
                    'doctoral_end_year'     => 'required|numeric|digits:4',
                    'doctoral_gpa'          => 'required',
                    'doctoral_degree'       => 'required',
                ];
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // STORE
            $educationData = [
                'employee_id'   => $request->employee_id,
                'last_education'   => $request->last_education,
                'primary_school'   => $request->primary_school,
                'ps_start_year'   => $request->ps_start_year,
                'ps_end_year'   => $request->ps_end_year,
                'ps_certificate'   => $request->ps_certificate,
                'junior_high_school'   => $request->junior_high_school,
                'jhs_start_year'   => $request->jhs_start_year,
                'jhs_end_year'   => $request->jhs_end_year,
                'jhs_certificate'   => $request->jhs_certificate,
                'senior_high_school'   => $request->senior_high_school,
                'shs_start_year'   => $request->shs_start_year,
                'shs_end_year'   => $request->shs_end_year,
                'shs_certificate'   => $request->shs_certificate,
                'bachelor_university'   => $request->bachelor_university,
                'bachelor_major'   => $request->bachelor_major,
                'bachelor_start_year'   => $request->bachelor_start_year,
                'bachelor_end_year'   => $request->bachelor_end_year,
                'bachelor_certificate'   => $request->bachelor_certificate,
                'bachelor_gpa'   => $request->bachelor_gpa,
                'bachelor_degree'   => $request->bachelor_degree,
                'master_university'   => $request->master_university,
                'master_major'   => $request->master_major,
                'master_start_year'   => $request->master_start_year,
                'master_end_year'   => $request->master_end_year,
                'master_certificate'   => $request->master_certificate,
                'master_gpa'   => $request->master_gpa,
                'master_degree'   => $request->master_degree,
                'doctoral_university'   => $request->doctoral_university,
                'doctoral_major'   => $request->doctoral_major,
                'doctoral_start_year'   => $request->doctoral_start_year,
                'doctoral_end_year'   => $request->doctoral_end_year,
                'doctoral_certificate'   => $request->doctoral_certificate,
                'doctoral_gpa'   => $request->doctoral_gpa,
                'doctoral_degree'   => $request->doctoral_degree,
            ];

            $education = EmployeeEducation::updateOrCreate(['employee_id' => $request->employee_id], $educationData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $education
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // FAMILY
    public function getEmployeeFamily($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Family
            $family = EmployeeFamily::where('employee_id', $employeeId)->first();

            if(empty($family)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Susunan keluarga karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($family->marital_status != 'kawin') {
                $family = [
                    'employee_id'               => $family->employee_id,
                    'father_name'               => $family->father_name,
                    'father_status'             => $family->father_status,
                    'father_age'                => $family->father_age,
                    'father_last_education'     => $family->father_last_education,
                    'father_last_job_title'     => $family->father_last_job_title,
                    'father_last_job_company'   => $family->father_last_job_company,
                    'mother_name'               => $family->mother_name,
                    'mother_status'             => $family->mother_status,
                    'mother_age'                => $family->mother_age,
                    'mother_last_education'     => $family->mother_last_education,
                    'mother_last_job_title'     => $family->mother_last_job_title,
                    'mother_last_job_company'   => $family->mother_last_job_company,
                    'marital_status'            => $family->marital_status,
                ];
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $family
            ], 200);

        } catch(Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeEmployeeFamily(Request $request)
    {
        try {
            // Validation Check
            $rules = [
                'employee_id'               => 'required',
                'father_name'               => 'required',
                'father_status'             => 'required|in:1,2',
                'father_last_education'     => 'required|in:sd,smp,sma,d i,d ii,d iii,s1,s2,s3',
                'mother_name'               => 'required',
                'mother_status'             => 'required|in:1,2',
                'mother_last_education'     => 'required|in:sd,smp,sma,d i,d ii,d iii,s1,s2,s3',
            ];

            if($request->father_status == 1) {
                $rules['father_age'] = 'required|numeric';
            }

            if($request->mother_status == 1) {
                $rules['mother_age'] = 'required|numeric';
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // CREATE
            $familyData = [
                'employee_id'               => $request->employee_id,
                'father_name'               => $request->father_name,
                'father_status'             => $request->father_status,
                'father_age'                => $request->father_age,
                'father_last_education'     => $request->father_last_education,
                'father_last_job_title'     => $request->father_last_job_title,
                'father_last_job_company'   => $request->father_last_job_company,
                'mother_name'               => $request->mother_name,
                'mother_status'             => $request->mother_status,
                'mother_age'                => $request->mother_age,
                'mother_last_education'     => $request->mother_last_education,
                'mother_last_job_title'     => $request->mother_last_job_title,
                'mother_last_job_company'   => $request->mother_last_job_company,
            ];

            $family = EmployeeFamily::updateOrCreate(['employee_id' => $request->employee_id], $familyData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $family
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // MARITAL STATUS
    public function updateEmployeeMarital($employeeId, Request $request)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Validation Check
            $rules = [
                'marital_status'    => 'required|in:kawin,belum kawin,janda,duda'
            ];

            if(!empty($request->marital_status) && $request->marital_status == 'kawin') {
                $rules += [
                    'couple_name'               => 'required',
                    'couple_age'                => 'required|numeric',
                    'couple_last_education'     => 'required|in:sd,smp,sma,d i,d ii,d iii,s1,s2,s3'
                ];
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
            $maritalStatus = $request->marital_status;

            $maritalData = [
                'marital_status'            => $maritalStatus,
                'couple_name'               => ($maritalStatus == 'kawin') ? $request->couple_name : null,
                'couple_age'                => ($maritalStatus == 'kawin') ? $request->couple_age : null,
                'couple_last_education'     => ($maritalStatus == 'kawin') ? $request->couple_last_education : null,
                'couple_last_job_title'     => ($maritalStatus == 'kawin') ? $request->couple_last_job_title : null,
                'couple_last_job_company'    => ($maritalStatus == 'kawin') ? $request->couple_last_job_company : null,
            ];

            EmployeeFamily::where('employee_id', $employeeId)->update($maritalData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $maritalData
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // SIBLING
    public function getAlSiblingByEmployeeId($employeeId)
    {
        try {
            $siblings = EmployeeSibling::where('employee_id', $employeeId)->get();

            if(count($siblings) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Saudara kandung tidak di temukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $siblings
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getSiblingById($employeeId, $siblingId)
    {
        try {
            // Employee Check
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Sibling
            $sibling = EmployeeSibling::find($siblingId);

            if(empty($sibling)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Saudara kandung tidak di temukan',
                    'data'      => []
                ], 200);
            }

            // Employee Siblings Correct
            if($employee->id != $sibling->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Saudara kandung tidak sesuai dengan karyawan',
                    'data'      => []
                ], 400);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $sibling
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function createEmployeeSibling(Request $request)
    {
        try {
            // Validation Check
            $rules = [
                'employee_id'               => 'required',
                'sibling_name'              => 'required',
                'sibling_gender'            => 'required|in:L,P',
                'sibling_status'            => 'required|in:1,2',
            ];

            if($request->sibling_status == 1) {
                $rules['sibling_age'] = 'required|numeric';
                $rules['sibling_last_education'] = 'required|in:sd,smp,sma,s1,s2,s3';
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Employee Check
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // CREATE
            $siblingData = [
                'employee_id'               => $request->employee_id,
                'sibling_name'              => $request->sibling_name,
                'sibling_gender'            => $request->sibling_gender,
                'sibling_age'               => $request->sibling_age,
                'sibling_last_education'    => $request->sibling_last_education,
                'sibling_last_job_title'    => $request->sibling_last_job_title,
                'sibling_last_job_company'  => $request->sibling_last_job_company,
            ];

            $sibling = EmployeeSibling::create($siblingData);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $sibling
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateEmployeeSibling($employeeId, $siblingId, Request $request)
    {
        try {
            // Employee Check
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Sibling Check
            $sibling = EmployeeSibling::find($siblingId);

            if(empty($sibling)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Saudara Kandung tidak ditemukan'
                ], 200);
            }

            // Employee Siblings Correct
            if($employee->id != $sibling->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Saudara kandung tidak sesuai dengan karyawan'
                ], 400);
            }

            // Validation Check
            $rules = [
                'sibling_name'              => 'required',
                'sibling_gender'            => 'required|in:L,P',
                'sibling_status'            => 'required|in:1,2',
            ];

            if($request->sibling_status == 1) {
                $rules['sibling_age'] = 'required|numeric';
                $rules['sibling_last_education'] = 'required|in:sd,smp,sma,s1,s2,s3';
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
            $siblingData = [
                'sibling_name'              => $request->sibling_name,
                'sibling_gender'            => $request->sibling_gender,
                'sibling_age'               => $request->sibling_age,
                'sibling_last_education'    => $request->sibling_last_education,
                'sibling_last_job_title'    => $request->sibling_last_job_title,
                'sibling_last_job_company'  => $request->sibling_last_job_company,
            ];

            EmployeeSibling::where('id', $siblingId)->update($siblingData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $siblingData
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteEmployeeSibling($employeeId, $siblingId)
    {
        try {
            // Employee Check
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Sibling
            $sibling = EmployeeSibling::find($siblingId);

            if(empty($sibling)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Saudara kandung tidak di temukan',
                    'data'      => []
                ], 200);
            }

            // Employee Siblings Correct
            if($employee->id != $sibling->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Saudara kandung tidak sesuai dengan karyawan',
                    'data'      => []
                ], 400);
            }

            // DELETE
            EmployeeSibling::where('id', $siblingId)->delete();

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

    // CHILDREN
    public function getAllChildrenByEmployeeId($employeeId)
    {
        try {
            $children = EmployeeChild::where('employee_id', $employeeId)->get();

            if(count($children) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Anak tidak di temukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $children
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getChildById($employeeId, $childId)
    {
        try {
            // Employee Check
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Children
            $child = EmployeeChild::find($childId);

            if(empty($child)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Anak tidak di temukan',
                    'data'      => []
                ], 200);
            }

            // Employee Children Correct
            if($employee->id != $child->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Anak tidak sesuai dengan karyawan',
                    'data'      => []
                ], 400);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $child
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function createEmployeeChild(Request $request)
    {
        try {
            // Validation Check
            $rules = [
                'employee_id'             => 'required',
                'child_name'              => 'required',
                'child_gender'            => 'required|in:L,P',
                'child_status'            => 'required|in:1,2'
            ];

            if($request->child_status == 1) {
                $rules['child_age'] = 'required|numeric';
                $rules['child_last_education'] = 'required|in:belum sekolah,sd,smp,sma,s1,s2,s3';
            }

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Employee Check
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // CREATE
            $childData = [
                'employee_id'             => $request->employee_id,
                'child_name'              => $request->child_name,
                'child_gender'            => $request->child_gender,
                'child_age'               => $request->child_age,
                'child_last_education'    => $request->child_last_education,
                'child_last_job_title'    => $request->child_last_job_title,
                'child_last_job_company'  => $request->child_last_job_company,
            ];

            $child = EmployeeChild::create($childData);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $child
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateEmployeeChild($employeeId, $childId, Request $request)
    {
        try {
            // Employee Check
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Child Check
            $child = EmployeeChild::find($childId);

            if(empty($child)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Anak tidak ditemukan'
                ], 200);
            }

            // Employee Siblings Correct
            if($employee->id != $child->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Anak tidak sesuai dengan karyawan'
                ], 400);
            }

            // Validation Check
            $rules = [
                'child_name'              => 'required',
                'child_gender'            => 'required|in:L,P',
                'child_status'            => 'required|in:1,2'
            ];

            if($request->child_status == 1) {
                $rules['child_age'] = 'required|numeric';
                $rules['child_last_education'] = 'required|in:belum sekolah,sd,smp,sma,s1,s2,s3';
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
            $childData = [
                'child_name'              => $request->child_name,
                'child_gender'            => $request->child_gender,
                'child_age'               => $request->child_age,
                'child_last_education'    => $request->child_last_education,
                'child_last_job_title'    => $request->child_last_job_title,
                'child_last_job_company'  => $request->child_last_job_company,
            ];

            EmployeeChild::where('id', $childId)->update($childData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $childData
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteEmployeeChild($employeeId, $childId)
    {
        try {
            // Employee Check
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Child
            $child = EmployeeChild::find($childId);

            if(empty($child)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Anak tidak di temukan',
                    'data'      => []
                ], 200);
            }

            // Employee Children Correct
            if($employee->id != $child->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Anak tidak sesuai dengan karyawan',
                    'data'      => []
                ], 400);
            }

            // DELETE
            EmployeeChild::where('id', $childId)->delete();

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

    // BANK
    public function getEmployeeBank($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Employee Bank
            $employeeBank = EmployeeBank::with(['bank'])->where('employee_id', $employeeId)->first();

            if(empty($employeeBank)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Data bank karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employeeBank
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeEmployeeBank(Request $request)
    {
        try {
            // Valiation Check
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'bank_id'           => 'required',
                'account_number'    => 'required',
                'account_name'      => 'required'
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
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Bank
            $bank = Bank::find($request->bank_id);

            if(empty($bank)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Bank tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Store
            $data = [
                'employee_id'       => $request->employee_id,
                'bank_id'           => $request->bank_id,
                'account_number'    => $request->account_number,
                'account_name'      => $request->account_name,
            ];

            $employeeBank = EmployeeBank::updateOrCreate(['employee_id' => $request->employee_id], $data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employeeBank
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // PHOTO SELFIE
    public function storeEmployeePhoto(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'photo'             => 'required|image|file|max:5120',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // GET EMPLOYEE
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // STORE
            $employeeId = $request->employee_id;

            // photo
            if($request->hasFile('photo')) {
                $photoFile = $request->file('photo');

                $photoPath = $photoFile->store("uploads/employee/$employeeId/profile/photo");

                if(!empty($employee->photo)) {
                    if(Storage::exists($employee->photo)) Storage::delete($employee->photo);
                }

                $employee->photo = $photoPath;
                $employee->save();

                return response()->json([
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'OK',
                    'data'      => $employee
                ], 200);

            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 500,
                    'message'   => 'Photo tidak terdeteksi!',
                    'data'      => []
                ], 500);
            }

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // DOCUMENT
    public function createEmployeeDocument(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required',
                'photo'             => 'image|file|max:5120',
                'ktp'               => 'mimes:pdf|file|max:5120',
                'kk'                => 'mimes:pdf|file|max:5120',
                'certificate'       => 'mimes:pdf|file|max:5120',
                'grade_transcript'  => 'mimes:pdf|file|max:5120',
                'certificate_skill' => 'mimes:pdf|file|max:5120',
                'bank_account'      => 'mimes:pdf|file|max:5120',
                'npwp'              => 'mimes:pdf|file|max:5120',
                'bpjs_ktn'          => 'mimes:pdf|file|max:5120',
                'bpjs_kes'          => 'mimes:pdf|file|max:5120',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // GET EMPLOYEE
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // STORE
            $employeeId = $request->employee_id;
            $document = EmployeeDocument::where('employee_id', $employeeId)->first();

            $documentData = [
                'employee_id'   => $employeeId
            ];

            // photo
            if($request->hasFile('photo')) {
                $photoFile = $request->file('photo');
                $photoOriName = $photoFile->getClientOriginalName();
                $photoFileName = $employeeId . '_' . Str::uuid() . '_' . $photoOriName;

                $photoPath = $photoFile->storeAs("uploads/employee/$employeeId/document", $photoFileName);
                $documentData['photo'] =  $photoPath;

                if(!empty($document)) {
                    if(!empty($document->photo)) {
                        if(Storage::exists($document->photo)) Storage::delete($document->photo);
                    }
                }
            }

            // ktp
            if($request->hasFile('ktp')) {
                $ktpFile = $request->file('ktp');
                $ktpOriName = $ktpFile->getClientOriginalName();
                $ktpFileName = $employeeId . '_' . Str::uuid() . '_' . $ktpOriName;

                $ktpPath = $ktpFile->storeAs("uploads/employee/$employeeId/document", $ktpFileName);
                $documentData['ktp'] = $ktpPath;

                if(!empty($document)) {
                    if(!empty($document->ktp)) {
                        if(Storage::exists($document->ktp)) Storage::delete($document->ktp);
                    }
                }
            }

            // kk
            if($request->hasFile('kk')) {
                $kkFile = $request->file('kk');
                $kkOriName = $kkFile->getClientOriginalName();
                $kkFileName = $employeeId . '_' . Str::uuid() . '_' . $kkOriName;

                $kkPath = $kkFile->storeAs("uploads/employee/$employeeId/document", $kkFileName);
                $documentData['kk'] = $kkPath;

                if(!empty($document)) {
                    if(!empty($document->kk)) {
                        if(Storage::exists($document->kk)) Storage::delete($document->kk);
                    }
                }
            }

            // certificate
            if($request->hasFile('certificate')) {
                $certificateFile = $request->file('certificate');
                $certificateOriName = $certificateFile->getClientOriginalName();
                $certificateFileName = $employeeId . '_' . Str::uuid() . '_' . $certificateOriName;

                $certificatePath = $certificateFile->storeAs("uploads/employee/$employeeId/document", $certificateFileName);
                $documentData['certificate'] = $certificatePath;

                if(!empty($document)) {
                    if(!empty($document->certificate)) {
                        if(Storage::exists($document->certificate)) Storage::delete($document->certificate);
                    }
                }
            }

            // Grade Transkrip
            if($request->hasFile('grade_transcript')) {
                $gradeTranscript = $request->file('grade_transcript');
                $gradeTranscriptOriName = $gradeTranscript->getClientOriginalName();
                $gradeTranscriptName = $employeeId . '_' . Str::uuid() . '_' . $gradeTranscriptOriName;

                $gradeTranscriptPath = $gradeTranscript->storeAs("uploads/employee/$employeeId/document", $gradeTranscriptName);
                $documentData['grade_transcript'] = $gradeTranscriptPath;

                if(!empty($document)) {
                    if(!empty($document->grade_transcript)) {
                        if(Storage::exists($document->grade_transcript)) Storage::delete($document->grade_transcript);
                    }
                }
            }

            // certificate skill
            if($request->hasFile('certificate_skill')) {
                $certificateSkillFile = $request->file('certificate_skill');
                $certificateSkillOriName = $certificateSkillFile->getClientOriginalName();
                $certificateSkillFileName = $employeeId . '_' . Str::uuid() . '_' . $certificateSkillOriName;

                $certificateSkillPath = $certificateSkillFile->storeAs("uploads/employee/$employeeId/document", $certificateSkillFileName);
                $documentData['certificate_skill'] = $certificateSkillPath;

                if(!empty($document)) {
                    if(!empty($document->certificate_skill)) {
                        if(Storage::exists($document->certificate_skill)) Storage::delete($document->certificate_skill);
                    }
                }
            }

            // Bank Account
            if($request->hasFile('bank_account')) {
                $bankAccountFile = $request->file('bank_account');
                $bankAccountOriName = $bankAccountFile->getClientOriginalName();
                $bankAccountFileName = $employeeId . '_' . Str::uuid() . '_' . $bankAccountOriName;

                $bankAccountPath = $bankAccountFile->storeAs("uploads/employee/$employeeId/document", $bankAccountFileName);
                $documentData['bank_account'] = $bankAccountPath;

                if(!empty($document)) {
                    if(!empty($document->bank_account)) {
                        if(Storage::exists($document->bank_account)) Storage::delete($document->bank_account);
                    }
                }
            }

            // npwp
            if($request->hasFile('npwp')) {
                $npwpFile = $request->file('npwp');
                $npwpOriName = $npwpFile->getClientOriginalName();
                $npwpFileName = $employeeId . '_' . Str::uuid() . '_' . $npwpOriName;

                $npwpPath = $npwpFile->storeAs("uploads/employee/$employeeId/document", $npwpFileName);
                $documentData['npwp'] = $npwpPath;

                if(!empty($document)) {
                    if(!empty($document->npwp)) {
                        if(Storage::exists($document->npwp)) Storage::delete($document->npwp);
                    }
                }
            }

            // BPJS Ketenagakerjaan
            if($request->hasFile('bpjs_ktn')) {
                $bpjsKtnFile = $request->file('bpjs_ktn');
                $bpjsKtnOriName = $bpjsKtnFile->getClientOriginalName();
                $bpjsKtnFileName = $employeeId . '_' . Str::uuid() . '_' . $bpjsKtnOriName;

                $bpjsKtnPath = $bpjsKtnFile->storeAs("uploads/employee/$employeeId/document", $bpjsKtnFileName);
                $documentData['bpjs_ktn'] = $bpjsKtnPath;

                if(!empty($document)) {
                    if(!empty($document->bpjs_ktn)) {
                        if(Storage::exists($document->bpjs_ktn)) Storage::delete($document->bpjs_ktn);
                    }
                }
            }

            // BPJS Kesehatan
            if($request->hasFile('bpjs_kes')) {
                $bpjsKesFile = $request->file('bpjs_kes');
                $bpjsKesOriName = $bpjsKesFile->getClientOriginalName();
                $bpjsKesFileName = $employeeId . '_' . Str::uuid() . '_' . $bpjsKesOriName;

                $bpjsKesPath = $bpjsKesFile->storeAs("uploads/employee/$employeeId/document", $bpjsKesFileName);
                $documentData['bpjs_kes'] = $bpjsKesPath;

                if(!empty($document)) {
                    if(!empty($document->bpjs_kes)) {
                        if(Storage::exists($document->bpjs_kes)) Storage::delete($document->bpjs_kes);
                    }
                }
            }

            $document = EmployeeDocument::updateOrCreate(['employee_id' => $employeeId], $documentData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $document
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteEmployeeDocument($employeeId)
    {
        try {
            // GET EMPLOYEE
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // GET EMPLOYEE DOCUMENT
            $document = EmployeeDocument::where('employee_id', $employeeId)->first();

            if(empty($document)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Dokumen tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Photo
            if(!empty($document->photo)) {
                if(Storage::exists($document->photo)) Storage::delete($document->photo);
            }

            // ktp
            if(!empty($document->ktp)) {
                if(Storage::exists($document->ktp)) Storage::delete($document->ktp);
            }

            // kk
            if(!empty($document->kk)) {
                if(Storage::exists($document->kk)) Storage::delete($document->kk);
            }

            // certificate
            if(!empty($document->certificate)) {
                if(Storage::exists($document->certificate)) Storage::delete($document->certificate);
            }

            // bank account
            if(!empty($document->bank_account)) {
                if(Storage::exists($document->bank_account)) Storage::delete($document->bank_account);
            }

            // npwp
            if(!empty($document->npwp)) {
                if(Storage::exists($document->npwp)) Storage::delete($document->npwp);
            }

            // bpjs ktn
            if(!empty($document->bpjs_ktn)) {
                if(Storage::exists($document->bpjs_ktn)) Storage::delete($document->bpjs_ktn);
            }

            // bpjs kes
            if(!empty($document->bpjs_kes)) {
                if(Storage::exists($document->bpjs_kes)) Storage::delete($document->bpjs_kes);
            }

            // DELETE
            EmployeeDocument::where('employee_id', $employeeId)->delete();

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

    public function getEmployeeDocument($employeeId)
    {
        try {
            // GET EMPLOYEE
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // GET EMPLOYEE DOCUMENT
            $document = EmployeeDocument::where('employee_id', $employeeId)->first();

            if(empty($document)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Dokumen tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $document
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // SIGNATURE
    public function createEmployeeSignature($employeeId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'signature'     => 'required|mimes:png|file|max:500'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // GET EMPLOYEE
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // delete
            if(!empty($employee->signature)) {
                if(Storage::exists($employee->signature)) Storage::delete($employee->signature);
            }
            // store
            $path = $request->file('signature')->store("uploads/employee/$employeeId/signature");
            Employee::where('id', $employeeId)->update(['signature' => $path]);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => [
                    'employee_id'   => $employeeId,
                    'signature'     => env('APP_URL') . "/storage/$path"
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getEmployeeSignature($employeeId)
    {
        try {
            // GET EMPLOYEE
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if(empty($employee->signature)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Tanda tangan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if(Storage::exists($employee->signature)) {
                return response()->json([
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'OK',
                    'data'      => [
                        'employee_id'   => $employeeId,
                        'signature'     => $employee->signature_url
                    ]
                ], 200);

            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Tanda tangan tidak ditemukan',
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

    // SKILL
    public function getEmployeeSkill($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Skill
            $employeeSkill = EmployeeSkill::where('employee_id', $employeeId)->get();

            if(count($employeeSkill) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Skill Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employeeSkill
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeEmployeeSkill($employeeId, Request $request)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Validation Check
            $validator = Validator::make($request->all(), [
                'skills'     => 'required|array'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // array to string
            $skills = $request->skills;
            $skillData = [];

            if(is_array($skills)) {
                foreach($skills as $skill) {
                    if(!empty($skill)) {
                        $data = [
                            'employee_id'   => $employeeId,
                            'skill'         => $skill
                        ];

                        EmployeeSkill::create($data);
                        $skillData[] = $skill;
                    }
                }
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => [
                    'employee_id'   => $employeeId,
                    'skill'         => $skillData
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateEmployeeSkill($employeeId, Request $request)
    {
        try {
            // validation check
            $validator = Validator::make($request->all(), [
                'skill_id'  => 'required',
                'skill'     => 'required'
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
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Skill
            $skill = EmployeeSkill::find($request->skill_id);

            if(empty($skill)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Skill tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // CHeck kesesuaian
            if($skill->employee_id != $employee->id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Skill dan karyawan tidak sesuai!',
                    'data'      => []
                ], 403);
            }

            // update
            $data = [
                'skill' => $request->skill
            ];

            EmployeeSkill::where('id', $skill->id)->update($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => EmployeeSkill::find($skill->id)
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteEmployeeSkill(Request $request)
    {
        try {
            // validation check
            $validator = Validator::make($request->all(), [
                'employee_id'   => 'required',
                'skill_id'      => 'required'
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
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Get Skill
            $skill = EmployeeSkill::find($request->skill_id);

            if(empty($skill)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Skill tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // CHeck kesesuaian
            if($skill->employee_id != $employee->id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Skill dan karyawan tidak sesuai!',
                    'data'      => []
                ], 403);
            }

            // Delete
            EmployeeSkill::where('id', $skill->id)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => []
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // WORK HOUR
    public function getEmployeeWorkHour($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Get Work Hour
            $workHour = EmployeeWorkHour::with([
                            'sundayCode',
                            'mondayCode',
                            'tuesdayCode',
                            'wednesdayCode',
                            'thursdayCode',
                            'fridayCode',
                            'saturdayCode',
                        ])->where('employee_id', $employeeId)->first();

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja karyawan tidak ditemukan',
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

    public function createEmployeeWorkHour(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'       => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Work Hour Check
            $workHourMsg = [];
            // sunday
            if(!empty($request->sunday)) {
                $workHour = WorkHour::where('work_hour_code', $request->sunday)->first();

                if(empty($workHour)) {
                    $workHourMsg['sunday'] = 'Jam kerja tidak ditemukan';
                }
            }
            // monday
            if(!empty($request->monday)) {
                $workHour = WorkHour::where('work_hour_code', $request->monday)->first();

                if(empty($workHour)) {
                    $workHourMsg['monday'] = 'Jam kerja tidak ditemukan';
                }
            }
            // tuesday
            if(!empty($request->tuesday)) {
                $workHour = WorkHour::where('work_hour_code', $request->tuesday)->first();

                if(empty($workHour)) {
                    $workHourMsg['tuesday'] = 'Jam kerja tidak ditemukan';
                }
            }
            // wednesday
            if(!empty($request->wednesday)) {
                $workHour = WorkHour::where('work_hour_code', $request->wednesday)->first();

                if(empty($workHour)) {
                    $workHourMsg['wednesday'] = 'Jam kerja tidak ditemukan';
                }
            }
            // thursday
            if(!empty($request->thursday)) {
                $workHour = WorkHour::where('work_hour_code', $request->thursday)->first();

                if(empty($workHour)) {
                    $workHourMsg['thursday'] = 'Jam kerja tidak ditemukan';
                }
            }
            // friday
            if(!empty($request->friday)) {
                $workHour = WorkHour::where('work_hour_code', $request->friday)->first();

                if(empty($workHour)) {
                    $workHourMsg['friday'] = 'Jam kerja tidak ditemukan';
                }
            }
            // saturday
            if(!empty($request->saturday)) {
                $workHour = WorkHour::where('work_hour_code', $request->saturday)->first();

                if(empty($workHour)) {
                    $workHourMsg['saturday'] = 'Jam kerja tidak ditemukan';
                }
            }

            if(!empty($workHourMsg)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => $workHourMsg
                ], 200);
            }

            // CREATE
            $data = [
                'employee_id'   => $request->employee_id,
                'sunday'        => $request->sunday,
                'monday'        => $request->monday,
                'tuesday'       => $request->tuesday,
                'wednesday'     => $request->wednesday,
                'thursday'      => $request->thursday,
                'friday'        => $request->friday,
                'saturday'      => $request->saturday,
            ];

            $employeeWorkHour = EmployeeWorkHour::updateOrCreate(['employee_id' => $request->employee_id], $data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employeeWorkHour
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteEmployeeWorkHour($employeeId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Get Work Hour
            $workHour = EmployeeWorkHour::where('employee_id', $employeeId)->first();

            if(empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            EmployeeWorkHour::where('employee_id', $employeeId)->delete();

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

    // CONFIRM DATA
    public function employeeConfirmData($employeeId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'confirm_date'  => 'required|date'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            if($employee->status > 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Karyawan telah mengonfirmasi data sebelumnya'
                ], 400);
            }

            if($employee->status < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Karyawan belum sampai tahap konfirmasi data'
                ], 400);
            }

            $confirmDate = $request->confirm_date;

            $employee->integrity_pact_check_date = $confirmDate;
            $employee->statement_letter_check_date = $confirmDate;
            $employee->biodata_confirm_date = $confirmDate;
            $employee->status = 2;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // ========================================

    /*==========================
            CONTRACT
    ==========================*/

    // JOBDESK
    public function getContractJobdesk($employeeId, $contractId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Get Contract
            $contract = EmployeeContract::find($contractId);

            if(empty($contract)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak tidak ditemukan'
                ], 200);
            }

            if($employee->id != $contract->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak tidak sesuai dengan karyawan'
                ], 200);
            }

            // Get Jobdesk
            $jobdesks = ContractJobdesk::where('contract_id', $contractId)->get();

            if(count($jobdesks) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jobdesk kontrak tidak ditemukan'
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $jobdesks
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function createContractJobdesk($employeeId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contract_id'   => 'required',
                'jobdesks'       => 'required|array'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Get Contract
            $contract = EmployeeContract::find($request->contract_id);

            if(empty($contract)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak tidak ditemukan'
                ], 200);
            }

            if($employee->id != $contract->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak tidak sesuai dengan karyawan'
                ], 200);
            }

            // CREATE
            $jobdeskData = [];
            $storeCount = 0;
            $jobdesks = $request->jobdesks;

            foreach($jobdesks as $jobdesk) {
                $job = ContractJobdesk::create([
                    'contract_id'   => $request->contract_id,
                    'jobdesk'       => $jobdesk
                ]);

                $jobdeskData[] = $job;
                $storeCount++;
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => [
                    'count'     => $storeCount,
                    'jobdesk'   => $jobdeskData
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteContractJobdesk($employeeId, $jobdeskId)
    {
        try {
            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Get Jobdesk
            $jobdesk = ContractJobdesk::find($jobdeskId);

            if(empty($jobdesk)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jobdesk tidak ditemukan'
                ], 200);
            }

            // Get Contract
            $contract = EmployeeContract::find($jobdesk->contract_id);

            if(empty($contract)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak tidak ditemukan'
                ], 200);
            }

            if($employee->id != $contract->employee_id) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak tidak sesuai dengan karyawan'
                ], 200);
            }

            // DELETE
            ContractJobdesk::where('id', $jobdeskId)->delete();

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

    // CONFIRM CONTRACT
    public function employeeConfirmContract($employeeId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'confirm_datetime'  => 'required|date_format:Y-m-d H:i:s'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($employeeId);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($employee->status != 6) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak pada tahap meninjau kontrak',
                    'data'      => []
                ], 200);
            }

            // Get Contract
            $employeeContract = EmployeeContract::where('id', $employee->contract_id)->first();

            if(empty($employeeContract)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak karyawan tidak ditemukan'
                ], 200);
            }

            if($employeeContract->status > 0) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Kontrak sudah dikonfirmasi'
                ], 200);
            }

            $confirmDatetime = $request->confirm_datetime;

            $employeeContract->check_contract = 1;
            $employeeContract->check_contract_datetime = $confirmDatetime;
            $employeeContract->status = 1;
            $employeeContract->save();

            $employee->status = 10;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // ========================================

    /*==========================
            SETTINGS
    ==========================*/

    public function employeeOvertimeSetting(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
               'employee_id'    => 'required',
               'is_overtime'    => 'required|boolean',
               'overtime_limit' => 'nullable|numeric|max:100|min:0'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get Employee
            $employee = Employee::find($request->employee_id);

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // UPDATE
            $employee->is_overtime = $request->is_overtime;
            $employee->overtime_limit = $request->overtime_limit;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function changeStatusEmployee(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required',
                'status'    => 'required|in:0,1,2,3,5,6,7,8,9,10,11'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get employee
            $employee = Employee::with(['contract', 'biodata', 'education', 'family', 'document', 'jobTitle', 'department', 'workHour', 'branch', 'bank'])
                                ->where('id', $request->employee_id)
                                ->first();

            if (empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Update
            $employee->status = $request->status;
            $employee->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $employee
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

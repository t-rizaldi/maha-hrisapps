<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeBiodata;
use App\Models\EmployeeContract;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_LETTER');
        $this->client = new Client();
    }

    // GET ALL EMPLOYEE]
    public function index()
    {
        try {
            $employee = Employee::with(['contract', 'biodata'])->get();

            if(count($employee) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Employee not found'
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
            $employee = Employee::with(['contract'])
                            ->where('id', $id)
                            ->first();

            if(empty($id) || empty($employee)) {
                return response()->json([
                    'status'        => 'error',
                    'code'          => 204,
                    'message'       => 'Employee not found'
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
                'show_contract'     => 'required'
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
                'contract_status'       => $employee->employee_status,
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
                'show_contract'         => $request->show_contract,
                'status'                => 1
            ];

            Employee::where('id', $request->employee_id)->update($employeeData);

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

    // ========================================

    /*==========================
            BIODATA
    ==========================*/
    // BIODATA
    public function storeEmployeeBiodata(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'employee_id'               => 'required|unique:employee_biodatas,employee_id',
                'fullname'                  => 'required',
                'nickname'                  => 'required',
                'nik'                       => 'required|digits:16|unique:employee_biodatas,nik',
                'identity_province'         => 'required|numeric',
                'identity_regency'          => 'required|numeric',
                'identity_district'         => 'required|numeric',
                'identity_village'          => 'required|numeric',
                'identity_postal_code'      => 'required|numeric:digits:5',
                'identity_address'          => 'required',
                'current_province'          => 'required|numeric',
                'current_regency'           => 'required|numeric',
                'current_district'          => 'required|numeric',
                'current_village'           => 'required|numeric',
                'current_postal_code'       => 'required|numeric:digits:5',
                'current_address'           => 'required',
                'residence_status'          => 'required',
                'phone_number'              => 'required|numeric|unique:employee_biodatas,phone_number',
                'emergency_phone_number'    => 'required|numeric',
                'start_work'                => 'required',
                'gender'                    => 'required',
                'birth_place'               => 'required',
                'birth_date'                => 'required',
                'religion'                  => 'required',
                'blood_type'                => 'required',
                'weight'                    => 'required|numeric',
                'height'                    => 'required|numeric',
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

            if($request->gender != 'L' && $request->gender != 'P') {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Pilihan gender L atau P'
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
                'start_work'                => $request->start_work,
                'gender'                    => $request->gender,
                'birth_place'               => $request->birth_place,
                'birth_date'                => $request->birth_date,
                'religion'                  => $request->religion,
                'blood_type'                => $request->blood_type,
                'weight'                    => $request->weight,
                'height'                    => $request->height,
            ];

            $biodata = EmployeeBiodata::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $biodata
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }

    }

    public function updateEmployeeBiodata($employeeId, Request $request)
    {
        try {
            // Get Employee
            $employee = Employee::with(['contract', 'biodata'])->where('id', $employeeId)->first();
            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Karyawan tidak ditemukan'
                ], 200);
            }

            // Validation Check
            $rules = [
                'fullname'                  => 'required',
                'nickname'                  => 'required',
                'nik'                       => 'required|digits:16',
                'identity_province'         => 'required|numeric',
                'identity_regency'          => 'required|numeric',
                'identity_district'         => 'required|numeric',
                'identity_village'          => 'required|numeric',
                'identity_postal_code'      => 'required|numeric:digits:5',
                'identity_address'          => 'required',
                'current_province'          => 'required|numeric',
                'current_regency'           => 'required|numeric',
                'current_district'          => 'required|numeric',
                'current_village'           => 'required|numeric',
                'current_postal_code'       => 'required|numeric:digits:5',
                'current_address'           => 'required',
                'residence_status'          => 'required',
                'phone_number'              => 'required|numeric',
                'emergency_phone_number'    => 'required|numeric',
                'start_work'                => 'required',
                'gender'                    => 'required',
                'birth_place'               => 'required',
                'birth_date'                => 'required',
                'religion'                  => 'required',
                'blood_type'                => 'required',
                'weight'                    => 'required|numeric',
                'height'                    => 'required|numeric',
            ];

            if($employee->biodata->nik != $request->nik) $rules['nik'] = 'required|digits:16|unique:employee_biodatas,nik';
            if($employee->biodata->phone_number != $request->phone_number) $rules['phone_number'] = 'required|unique:employee_biodatas,phone_number';

            $validator = Validator::make($request->all(), $rules);

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

            if($request->gender != 'L' && $request->gender != 'P') {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Pilihan gender L atau P'
                ], 400);
            }

            // store
            $data = [
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
                'start_work'                => $request->start_work,
                'gender'                    => $request->gender,
                'birth_place'               => $request->birth_place,
                'birth_date'                => $request->birth_date,
                'religion'                  => $request->religion,
                'blood_type'                => $request->blood_type,
                'weight'                    => $request->weight,
                'height'                    => $request->height,
            ];

            $biodata = EmployeeBiodata::where('employee_id', $employeeId)->update($data);

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
}

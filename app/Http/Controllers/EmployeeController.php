<?php

namespace App\Http\Controllers;

use App\Models\Employee;
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
            $employee = Employee::with(['contract'])->get();

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
            if($employee->status == 1) {
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
}

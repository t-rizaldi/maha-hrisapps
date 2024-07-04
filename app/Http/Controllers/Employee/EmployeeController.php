<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class EmployeeController extends Controller
{
    private $api;
    private $client;
    private $jwtSecret;
    private $jwtAccessTokenExpired;
    private $jwtRefreshTokenExpired;
    private $configuration;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_EMPLOYEE');
        $this->jwtSecret = env('JWT_SECRET');
        $this->jwtAccessTokenExpired = env('JWT_ACCESS_TOKEN_EXPIRED');
        $this->jwtRefreshTokenExpired = env('JWT_REFRESH_TOKEN_EXPIRED');

        $this->configuration = Configuration::forSymmetricSigner(
                                new Sha256(),
                                InMemory::base64Encoded($this->jwtSecret)
                            );

        $this->client = new Client();
    }

    /*===========================
                AUTH
    ===========================*/

    // register
    public function register(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/register", [
                'json'  => $request->all()
            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // login
    public function login(Request $request)
    {
        try {
            // cek validasi
            $validator = Validator::make($request->all(), [
                'email'     => 'required|email',
                'password'  => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // POST DATA
            $data = [
                'email'     => $request->email,
                'password'  => $request->password
            ];

            $responseData = $this->client->post("$this->api/login", [
                                'json' => $data,
                            ])->getBody()->getContents();

            $employee = json_decode($responseData, true);

            // membuat token
            $now = new DateTimeImmutable();

            // access token
            $token = $this->configuration->builder()
                    ->issuedAt($now)
                    ->expiresAt($now->modify("+$this->jwtAccessTokenExpired day"))
                    ->withClaim('data', $employee['data'])
                    ->getToken($this->configuration->signer(), $this->configuration->signingKey())
                    ->toString();

            // refresh token
            $refreshToken = $this->configuration->builder()
                            ->issuedAt($now)
                            ->expiresAt($now->modify("+$this->jwtRefreshTokenExpired day"))
                            ->withClaim('data', $employee['data'])
                            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
                            ->toString();

            // save refresh token
            $this->client->post("$this->api/refresh-token", [
                'json' => [
                    'employee_id'       => $employee['data']['id'],
                    'refresh_token'     => $refreshToken
                ],
            ]);

            return response()->json([
                'status'        => 'success',
                'code'          => 200,
                'message'       => 'OK',
                'data'          => [
                    'token'         => $token,
                    'refresh_token' => $refreshToken
                ]
            ], 200);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // logout
    public function logout(Request $request)
    {
        try {
            $employee = $request->get('token_payload');

            $data = [
                'employee_id'   => $employee['id']
            ];

            $responseData = $this->client->post("$this->api/logout", [
                                'json'  => $data
                            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);

            return response()->json($response, $statusCode);
        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // REFRESH TOKEN
    public function refreshToken(Request $request)
    {

        try {
            // cek validasi
            $validator = Validator::make($request->all(), [
                'refresh_token' => 'required',
                'email'         => 'required|email'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // cek refresh token
            $responseData = $this->client->get("$this->api/refresh-token/$request->refresh_token");
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);

            // status check
            if($response['status'] == 'error') return response()->json($response, $statusCode);

            $refreshToken = $response['data']['token'];
            $refreshTokenParse = $this->configuration->parser()->parse((string) $refreshToken);

            // cek kadaluwarsa token
            $currentTimestamp = time();
            $expirationTimestamp = $refreshTokenParse->claims()->get('exp')->getTimestamp();

            if ($expirationTimestamp !== null && $expirationTimestamp < $currentTimestamp) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Refresh token expired'
                ], 400);
            }

            // cek email
            $tokenData = $refreshTokenParse->claims()->get('data');

            if($request->email != $tokenData['email']) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'email is not valid'
                ], 400);
            }

            // new access token
            $now = new DateTimeImmutable();
            $token = $this->configuration->builder()
                    ->issuedAt($now)
                    ->expiresAt($now->modify("+$this->jwtAccessTokenExpired day"))
                    ->withClaim('data', $tokenData)
                    ->getToken($this->configuration->signer(), $this->configuration->signingKey())
                    ->toString();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'token'     => $token
            ], 200);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/change-password", [
                'json'  => $request->all()
            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // ====================================================

    /*================================
                EMPLOYEE CRUD
    ================================*/
    // Get All Employees
    public function index(Request $request)
    {
        try {
            $params = [];

            if($request->has('role_id')) {
                $roleId = $request->query('role_id');
                $params['query']['role_id'] = $roleId;
            }

            if($request->has('department_code')) {
                $departmentCode = $request->query('department_code');
                $params['query']['department_code'] = $departmentCode;
            }

            if($request->has('job_title')) {
                $jobTitle = $request->query('job_title');
                $params['query']['job_title'] = $jobTitle;
            }

            if($request->has('status')) {
                $status = $request->query('status');
                $params['query']['status'] = $status;
            }

            $responseData = $this->client->get("$this->api", $params);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // Get Employee By Id
    public function getEmployeeById($id = null)
    {
        try {
            $responseData = $this->client->get("$this->api/$id");
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // Get Employee By Token
    public function getEmployeeByToken(Request $request)
    {
        try {
            $employee = $request->get('token_payload');

            if(empty($employee)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Data tidak ditemukan'
                ], 400);
            }

            $dataResponse = [
                'id'            => $employee['id'],
                'fullname'      => $employee['fullname'],
                'email'         => $employee['email'],
                'role_id'       => $employee['role_id'],
                'job_title'     => $employee['job_title'],
                'department'    => $employee['department'],
                'branch'        => $employee['branch'],
            ];

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $dataResponse
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    //==========================================
    /*==========================
            BIODATA
    ==========================*/

    // BIODATA
    public function getEmployeeBiodata($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-biodata/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function storeEmployeeBiodata(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-biodata", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // EDUCATION
    public function getEmployeeEducation($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-education/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function storeEmployeeEducation(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-education", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // FAMILY
    public function getEmployeeFamily($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-family/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function storeEmployeeFamily(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-family", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // MARITAL
    public function updateEmployeeMarital($employeeId, Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/employee-marital/$employeeId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // SIBLING
    public function createEmployeeSibling(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-sibling", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function updateEmployeeSibling($employeeId, $siblingId, Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/employee-sibling/$employeeId/$siblingId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function deleteEmployeeSibling($employeeId, $siblingId)
    {
        try {
            $responseData = $this->client->delete("$this->api/employee-sibling/$employeeId/$siblingId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function getAllSiblingByEmployeeId($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-sibling/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function getSiblingById($employeeId, $siblingId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-sibling/$employeeId/$siblingId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // CHILDREN
    public function createEmployeeChildren(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-children", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function updateEmployeeChildren($employeeId, $childId, Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/employee-children/$employeeId/$childId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function deleteEmployeeChildren($employeeId, $childId)
    {
        try {
            $responseData = $this->client->delete("$this->api/employee-children/$employeeId/$childId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function getAllChildrenByEmployeeId($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-children/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function getChildrenById($employeeId, $childrenId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-children/$employeeId/$childrenId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // BANK
    public function getEmployeeBank($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-bank/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function storeEmployeeBank(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-bank", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // DOCUMENT
    public function getEmployeeDocument($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-document/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function deleteEmployeeDocument($employeeId)
    {
        try {
            $responseData = $this->client->delete("$this->api/employee-document/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function storeEmployeeDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id'   => 'required',
                'photo'         => 'image|file|max:5120',
                'ktp'           => 'mimes:pdf|file|max:5120',
                'kk'            => 'mimes:pdf|file|max:5120',
                'certificate'   => 'mimes:pdf|file|max:5120',
                'bank_account'  => 'mimes:pdf|file|max:5120',
                'npwp'          => 'mimes:pdf|file|max:5120',
                'bpjs_ktn'      => 'mimes:pdf|file|max:5120',
                'bpjs_kes'      => 'mimes:pdf|file|max:5120',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }



            if($request->hasFile('photo')) {
                $httpRequest = $httpRequest->attach(
                    'photo', file_get_contents($request->file('photo')->getRealPath()), $request->file('photo')->getClientOriginalName()
                );
            }

            if($request->hasFile('ktp')) {
                $httpRequest = $httpRequest->attach(
                    'ktp', file_get_contents($request->file('ktp')->getRealPath()), $request->file('ktp')->getClientOriginalName()
                );
            }

            if($request->hasFile('kk')) {
                $httpRequest = $httpRequest->attach(
                    'kk', file_get_contents($request->file('kk')->getRealPath()), $request->file('kk')->getClientOriginalName()
                );
            }

            if($request->hasFile('certificate')) {
                $httpRequest = $httpRequest->attach(
                    'certificate', file_get_contents($request->file('certificate')->getRealPath()), $request->file('certificate')->getClientOriginalName()
                );
            }

            if($request->hasFile('bank_account')) {
                $httpRequest = $httpRequest->attach(
                    'bank_account', file_get_contents($request->file('bank_account')->getRealPath()), $request->file('bank_account')->getClientOriginalName()
                );
            }


            if($request->hasFile('npwp')) {
                $httpRequest = $httpRequest->attach(
                    'npwp', file_get_contents($request->file('npwp')->getRealPath()), $request->file('npwp')->getClientOriginalName()
                );
            }

            if($request->hasFile('bpjs_ktn')) {
                $httpRequest = $httpRequest->attach(
                    'bpjs_ktn', file_get_contents($request->file('bpjs_ktn')->getRealPath()), $request->file('bpjs_ktn')->getClientOriginalName()
                );
            }

            if($request->hasFile('bpjs_kes')) {
                $httpRequest = $httpRequest->attach(
                    'bpjs_kes', file_get_contents($request->file('bpjs_kes')->getRealPath()), $request->file('bpjs_kes')->getClientOriginalName()
                );
            }

            $response = $httpRequest->post("$this->api/employee-document", [
                'employee_id'   => $request->employee_id
            ]);

            return response()->json($response->json(), $response->status());

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
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

            if($request->hasFile('signature')) {
                $response = Http::attach(
                    'signature', file_get_contents($request->file('signature')->getRealPath()), $request->file('signature')->getClientOriginalName()
                )->post("$this->api/employee-signature/$employeeId");

                return response()->json($response->json(), $response->status());
            } else {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Signature tidak boleh kosong'
                ], 400);
            }

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function getEmployeeSignature($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-signature/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // SKILL
    public function getEmployeeSkill($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-skill/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function updateEmployeeSkill($employeeId, Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-skill/$employeeId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // WORK HOUR
    public function getEmployeeWorkHour($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-work-hour/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function createEmployeeWorkHour(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-work-hour", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function deleteEmployeeWorkHour($employeeId)
    {
        try {
            $responseData = $this->client->delete("$this->api/employee-work-hour/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // CONFIRM DATA
    public function employeeConfirmData($employeeId, Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-confirm-data/$employeeId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // ========================================

    /*==========================
            CONTRACT
    ==========================*/

    public function getContractJobdesk($employeeId, $contractId)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-contract/$employeeId/$contractId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function createContractJobdesk($employeeId, Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-contract/$employeeId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function deleteContractJobdesk($employeeId, $jobdeskId)
    {
        try {
            $responseData = $this->client->delete("$this->api/employee-contract/$employeeId/$jobdeskId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // CONFIRM CONTRACT
    public function employeeConfirmContract($employeeId, Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/employee-confirm-contract/$employeeId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // ========================================

    /*================================
                VERIFICATION
    ================================*/

    // REGISTER
    public function verifyRegister(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/verify-register", [
                'json'  => $request->all()
            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function rejectRegister(Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/reject-register", [
                'json'  => $request->all()
            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // BIODATA
    public function verifyData($employeeId, Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/verify-data/$employeeId", [
                'json'  => $request->all()
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function verifyDataPhaseTwo($employeeId)
    {
        try {
            $responseData = $this->client->put("$this->api/verify-data-phase-two/$employeeId");

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function rejectData(Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/reject-data", [
                'json'  => $request->all()
            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function rejectDataPhaseTwo(Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/reject-data-phase-two", [
                'json'  => $request->all()
            ]);
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return response()->json($response, $statusCode);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }
}

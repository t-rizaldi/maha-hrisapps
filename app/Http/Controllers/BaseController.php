<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected $apiEmployee;
    protected $apiGeocodingMaps;
    protected $mapsApiKey;
    protected $client;
    protected $messageSuccess;
    protected $messageError;
    protected $messageNotFound;
    protected $messageCreated;
    protected $messageUpdated;
    protected $messageDeleted;

    public function __construct()
    {
        $this->apiEmployee = env('URL_SERVICE_EMPLOYEE');
        $this->apiGeocodingMaps = env('URL_GEOCODING_API_MAPS');
        $this->mapsApiKey = env('MAPBOX_API_KEY');
        $this->client = new Client();

        $this->messageSuccess = 'success';
        $this->messageError = 'error';
        $this->messageNotFound = ' tidak ditemukan !';
        $this->messageCreated = ' berhasil ditambah !';
        $this->messageUpdated = ' berhasil diubah !';
        $this->messageDeleted = ' berhasil dihapus !';
    }

    // Get Employee
    public function getEmployee($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->apiEmployee/$employeeId");
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return $response;

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);

            return $response;
        }
    }

    public function getEmployeeNotAbsent($date, $ids = [])
    {
        try {
            $response = $this->client->get("$this->apiEmployee/employee/not-absent", [
                'query' => [
                    'employee_id' => implode(',', $ids),
                    'date' => $date
                ]
            ]);
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);
            if ($responseData['status'] === 'success') {
                return $responseData['data'];
            }
            return [];
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);
            return [];
        }
    }

    public function getEmployeeByParams($paramsArr = [])
    {
        try {
            $responseData = $this->client->get("$this->apiEmployee", [
                'query' => $paramsArr
            ]);

            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return $response;

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);

            return $response;
        }
    }

    // Get Employee Work Hour
    public function getEmployeeWorkHour($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->apiEmployee/employee-work-hour/$employeeId");
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return $response;

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);

            return $response;
        }
    }

    // Get branch
    public function getBranch($code)
    {
        try {
            $responseData = $this->client->get("$this->apiEmployee/branch/$code/detail");
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return $response;

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);

            return $response;
        }
    }

    // Get branch
    public function getAllDepartment($paramsArr = [])
    {
        try {
            $responseData = $this->client->get("$this->apiEmployee/department", [
                'query' => $paramsArr
            ]);
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);
            return $response;

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body, true);

            return $response;
        }
    }

    // Get Approver by Structure
    public function getApproverByStructure($employeeId)
    {
        try {
            // Get Employee
            $employee = $this->getEmployee($employeeId);

            if($employee['status'] == 'error') {
                return response()->json($employee, 200);
            }

            $employee = $employee['data'];
            $dept = $employee['department'];
            $gmNum = $dept['gm_num'];

            // Manager
            $manager = $this->getEmployeeByParams([
                'department_code'   => $dept['department_code'],
                'role_id'           => 2,
                'status'            => 3
            ]);

            if($manager['status'] == 'success') {
                $manager = $manager['data'][0];
            } else {
                $manager = [];
            }

            // HRD Manager
            $hrdManager = $this->getEmployeeByParams([
                        'department_code'   => 'HR',
                        'role_id'           => 2,
                        'status'            => 3
                    ]);

            if($hrdManager['status'] == 'success') {
                $hrdManager = $hrdManager['data'][0];
            } else {
                $hrdManager = [];
            }

            // HRD SPV
            $hrdSpv = $this->getEmployeeByParams([
                        'department_code'   => 'HR',
                        'role_id'           => 1,
                        'job_title'         => 34,
                        'status'            => 3
                    ]);

            if($hrdSpv['status'] == 'success') {
                $hrdSpv = $hrdSpv['data'][0];
            } else {
                $hrdSpv = [];
            }

            // GM
            if(!empty($gmNum)) {
                $gm = $this->getEmployeeByParams([
                    'department_code'   => 'GM',
                    'role_id'           => 3,
                    'status'            => 3
                ]);

                if($gm['status'] == 'success') {
                    $data = $gm['data'];

                    $gm = [];

                    foreach($data as $dt) {
                        if($dt['job_title']['gm_num'] == $gmNum) $gm = $dt;
                    }

                } else {
                    $gm = [];
                }
            } else {
                $gm = [];
            }

            // Director
            $director = $this->getEmployeeByParams([
                'department_code'   => 'DU',
                'role_id'           => 4,
                'job_title'         => 2,
                'status'            => 3
            ]);

            if($director['status'] == 'success') {
                $director = $director['data'][0];
            } else {
                $director = [];
            }

            // Commisioner
            $commisioner = $this->getEmployeeByParams([
                'department_code'   => 'KM',
                'role_id'           => 5,
                'job_title'         => 1,
                'status'            => 3
            ]);

            if($commisioner['status'] == 'success') {
                $commisioner = $commisioner['data'][0];
            } else {
                $commisioner = [];
            }

            return [
                'manager'       => $manager,
                'hrdManager'    => $hrdManager,
                'hrdSpv'        => $hrdSpv,
                'gm'            => $gm,
                'director'      => $director,
                'commisioner'   => $commisioner,
            ];

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

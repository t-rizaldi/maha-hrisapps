<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AttendanceController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_ATTENDANCE');
        $this->client = new Client();
    }

    /*=================================
                ATTENDANCE
    =================================*/

    public function employeeAttendanceHistory($employeeId, $startDate, $endDate)
    {
        try {
            $responseData = $this->client->get("$this->api/employee-history/$employeeId/$startDate/$endDate");
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

    /*========Photo Attendance==========*/
    // Store
    public function storeAttendance(Request $request)
    {
        try {
            $httpRequest = Http::asMultipart();

            if($request->hasFile('attendance_photo')) {
                $httpRequest = $httpRequest->attach(
                    'attendance_photo', file_get_contents($request->file('attendance_photo')->getRealPath()), $request->file('attendance_photo')->getClientOriginalName()
                );
            }

            $response = $httpRequest->post("$this->api", [
                'employee_id'   => $request->employee_id,
                'attendance_date'   => $request->attendance_date,
                'attendance_time'   => $request->attendance_time,
                'attendance_location'   => $request->attendance_location,
                'attendance_branch'   => $request->attendance_branch,
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

    /*=================================
            Monitoring Attendance
    =================================*/

    public function getTodayStatistic()
    {
        try {
            $responseData = $this->client->get("$this->api/today-statistic");
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

    public function attendanceHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if($request->has('status')) {
                $params['query']['status'] = $request->query('status');
            }

            if($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/$startDate/$endDate", $params);
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

    public function overtimeHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('status')) {
                $params['query']['status'] = $request->query('status');
            }

            if ($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/overtime/$startDate/$endDate", $params);
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

    public function lateHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('status')) {
                $params['query']['status'] = $request->query('status');
            }

            if ($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/late/$startDate/$endDate", $params);
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

    public function notAbsentHomeHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('status')) {
                $params['query']['status'] = $request->query('status');
            }

            if ($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/not_absent_home/$startDate/$endDate", $params);
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

    public function permitHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('dept_id')) {
                $params['query']['dept_id'] = $request->query('dept_id');
            }

            if ($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/permit/$startDate/$endDate", $params);
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

    public function sickHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('dept_id')) {
                $params['query']['dept_id'] = $request->query('dept_id');
            }

            if ($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/sick/$startDate/$endDate", $params);
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

    public function leaveHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('dept_id')) {
                $params['query']['dept_id'] = $request->query('dept_id');
            }

            if ($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/leave/$startDate/$endDate", $params);
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

    public function notAbsentHistory($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if ($request->has('branch_attendance')) {
                $params['query']['branch_attendance'] = $request->query('branch_attendance');
            }

            if ($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/history/not_absent/$startDate/$endDate", $params);
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

    //=========================================

    /*=================================
                OVERTIME
    =================================*/

    public function getAllEmployeeOvertimeBydate($startDate, $endDate, Request $request)
    {
        try {
            $params = [];

            if($request->has('by_approved_date')) {
                $params['query']['by_approved_date'] = $request->query('by_approved_date');
            }

            if($request->has('branch_code')) {
                $params['query']['branch_code'] = $request->query('branch_code');
            }

            if($request->has('approved_status')) {
                $params['query']['approved_status'] = $request->query('approved_status');
            }

            if($request->has('employee_id')) {
                $params['query']['employee_id'] = $request->query('employee_id');
            }

            $responseData = $this->client->get("$this->api/overtime/$startDate/$endDate", $params);
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

    public function getOvertimeByEmployeeId($employeeId)
    {
        try {
            $responseData = $this->client->get("$this->api/overtime/$employeeId");

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

    public function storeOvertime(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/overtime", [
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

    public function updateOvertime($employeeId, $overtimeDate, Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/overtime/$employeeId/$overtimeDate", [
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

    public function deleteOvertime($employeeId, $overtimeDate)
    {
        try {
            $responseData = $this->client->delete("$this->api/overtime/$employeeId/$overtimeDate");

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

    public function submitOvertime(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/overtime/submit", [
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

    public function rejectOvertime(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/overtime/reject", [
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

    public function approveOvertime(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/overtime/approve", [
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

    public function getOvertimeByApprover($approverId)
    {
        try {
            $responseData = $this->client->get("$this->api/overtime/list-by-approve/$approverId");

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

    public function overtimeOrderStore(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/overtime/order", [
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

    //======Overtime Photo========
    public function storeOvertimePhoto($employeeId, $overtimeId, Request $request)
    {
        try {
            $httpRequest = Http::asMultipart();

            if($request->hasFile('photo')) {
                $httpRequest = $httpRequest->attach(
                    'photo', file_get_contents($request->file('photo')->getRealPath()), $request->file('photo')->getClientOriginalName()
                );
            }

            $response = $httpRequest->post("$this->api/overtime/photo/$employeeId/$overtimeId", [
                'time'      => $request->time,
                'status'    => $request->status,
                'location'  => $request->location,
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
}

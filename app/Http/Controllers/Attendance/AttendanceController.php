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

    //=========================================

    /*=================================
                OVERTIME
    =================================*/

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

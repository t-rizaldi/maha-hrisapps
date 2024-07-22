<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AttendanceWorkerController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_ATTENDANCE') . '/worker';
        $this->client = new Client();
    }

    /*=================================
                ATTENDANCE
    =================================*/

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
                'worker_id'             => $request->worker_id,
                'attendance_date'       => $request->attendance_date,
                'attendance_time'       => $request->attendance_time,
                'attendance_location'   => $request->attendance_location,
                'attendance_branch'     => $request->attendance_branch,
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

    public function storeOvertime(Request $request)
    {
        try {
            $httpRequest = Http::asMultipart();

            if ($request->hasFile('overtime_photo')) {
                $httpRequest = $httpRequest->attach(
                    'overtime_photo',
                    file_get_contents($request->file('overtime_photo')->getRealPath()),
                    $request->file('overtime_photo')->getClientOriginalName()
                );
            }

            // $response = $httpRequest->post("$this->api/overtime", [
            //     'worker_id'             => $request->worker_id,
            //     'status'               => $request->status,
            //     'overtime_date'         => $request->overtime_date,
            //     'overtime_start'         => $request->overtime_start,
            //     'overtime_finish'        => $request->overtime_finish,
            //     'overtime_start_location'     => $request->overtime_start_location,
            //     'overtime_finish_location'    => $request->overtime_finish_location,
            // ]);

            $response = $httpRequest->post("$this->api/overtime", $request->all());

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

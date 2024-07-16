<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;

class LeaveController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_ATTENDANCE') . '/leave/application';
        $this->client = new Client();
    }

    public function getAllLeave()
    {
        try {
            $responseData = $this->client->get("$this->api");
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

    public function getLeaveByID($id)
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

    public function getLeaveByEmployeeID($id)
    {
        try {
            $responseData = $this->client->get("$this->api/employee/$id");
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

    public function getAllLeaveByApprover($id)
    {
        try {
            $responseData = $this->client->get("$this->api/list-by-approver/$id");
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

    public function storeLeave(Request $request)
    {
        try {
            $httpRequest = Http::asMultipart();
            if ($request->hasFile('attachment')) {
                $httpRequest = $httpRequest->attach(
                    'attachment',
                    file_get_contents($request->file('attachment')->getRealPath()),
                    $request->file('attachment')->getClientOriginalName()
                );
            }
            $data = [
                'employee_id' => $request->employee_id,
                'permit_type_id' => $request->permit_type_id,
                'employee_create_id' => $request->employee_create_id,
                'leave_start_date' => $request->leave_start_date,
                'leave_end_date' => $request->leave_end_date,
                'description' => $request->description,
            ];
            $response = $httpRequest->post("$this->api", $data);
            return response()->json($response->json(), $response->status());
        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);
            return response()->json([$response], $statusCode);
        }
    }

    public function updateLeave(Request $request, $id)
    {
        try {
            $httpRequest = Http::asMultipart();
            if ($request->hasFile('attachment')) {
                $httpRequest = $httpRequest->attach(
                    'attachment',
                    file_get_contents($request->file('attachment')->getRealPath()),
                    $request->file('attachment')->getClientOriginalName()
                );
            }
            $data = [
                'employee_id' => $request->employee_id,
                'permit_type_id' => $request->permit_type_id,
                'employee_create_id' => $request->employee_create_id,
                'leave_start_date' => $request->leave_start_date,
                'leave_end_date' => $request->leave_end_date,
                'description' => $request->description,
            ];
            $response = $httpRequest->post("$this->api/update/$id", $data);
            return response()->json($response->json(), $response->status());
        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);
            return response()->json([$response], $statusCode);
        }
    }

    public function approveLeave(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/approve", [
                'json' => $request->all()
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

    public function rejectLeave(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/reject", [
                'json' => $request->all()
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

    public function deleteLeave($id)
    {
        try {
            $responseData = $this->client->delete("$this->api/$id");
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

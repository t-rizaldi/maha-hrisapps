<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;

class SickController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_ATTENDANCE') . '/sick/application';
        $this->client = new Client();
    }

    public function getAllSick()
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

    public function getSickByID($id)
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

    public function getSickByEmployeeID($id)
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

    public function getAllSickByApprover($id)
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

    public function storeSick(Request $request)
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
                'employee_create_id' => $request->employee_create_id,
                'sick_start_date' => $request->sick_start_date,
                'sick_end_date' => $request->sick_end_date,
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

    public function updateSick(Request $request, $id)
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
                'employee_create_id' => $request->employee_create_id,
                'sick_start_date' => $request->sick_start_date,
                'sick_end_date' => $request->sick_end_date,
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

    public function approveSick(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/approve", [
                'json' => $request->all(),
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

    public function rejectSick(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/reject", [
                'json' => $request->all(),
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


    public function deleteSick($id)
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

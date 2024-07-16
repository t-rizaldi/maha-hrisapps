<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;

class PermitController extends Controller
{
    private $apiPermit;
    private $apiPermitType;
    private $client;

    public function __construct()
    {
        $this->apiPermit = env('URL_SERVICE_ATTENDANCE') . '/permit/application';
        $this->apiPermitType = env('URL_SERVICE_ATTENDANCE') . '/permit/type';
        $this->client = new Client();
    }

    /*=================================
                Permit Type
    =================================*/

    public function getAllPermitType()
    {
        try {
            $responseData = $this->client->get("$this->apiPermitType/all");
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

    public function getPermitTypeById($id)
    {
        try {
            $responseData = $this->client->get("$this->apiPermitType/$id");
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

    public function getPermitByType(Request $request)
    {
        try {
            $params = [];
            if ($request->has('type')) {
                $params['query']['type'] = $request->query('type');
            }
            $responseData = $this->client->get("$this->apiPermitType", $params);
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

    public function storePermitType(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->apiPermitType", [
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

    public function updatePermitType(Request $request, $id)
    {
        try {
            $responseData = $this->client->put("$this->apiPermitType/$id", [
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

    public function deletePermitType($id)
    {
        try {
            $responseData = $this->client->delete("$this->apiPermitType/$id");
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

    /*=================================
                Permit
    =================================*/

    public function getAllPermit()
    {
        try {
            $responseData = $this->client->get("$this->apiPermit");
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

    public function getPermitById($id)
    {
        try {
            $responseData = $this->client->get("$this->apiPermit/$id");
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

    public function getPermitByEmployeeID($id)
    {
        try {
            $responseData = $this->client->get("$this->apiPermit/employee/$id");
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

    public function getAllPermitByApprover($id)
    {
        try {
            $responseData = $this->client->get("$this->apiPermit/list-by-approver/$id");
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
    public function storePermit(Request $request)
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
                'permit_start_date' => $request->permit_start_date,
                'permit_end_date' => $request->permit_end_date,
                'permit_start_time' => $request->permit_start_time,
                'permit_end_time' => $request->permit_end_time,
                'description' => $request->description,
            ];
            $response = $httpRequest->post("$this->apiPermit", $data);
            return response()->json($response->json(), $response->status());
        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }
    public function updatePermit(Request $request, $id)
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
                'permit_start_date' => $request->permit_start_date,
                'permit_end_date' => $request->permit_end_date,
                'permit_start_time' => $request->permit_start_time,
                'permit_end_time' => $request->permit_end_time,
                'description' => $request->description,
            ];
            $response = $httpRequest->post("$this->apiPermit/update/$id", $data);
            return response()->json($response->json(), $response->status());
        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function approvePermit(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->apiPermit/approve", [
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

    public function rejectPermit(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->apiPermit/reject", [
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

    public function deletePermit($id)
    {
        try {
            $responseData = $this->client->delete("$this->apiPermit/$id");
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

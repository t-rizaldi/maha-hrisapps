<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WorkerController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_EMPLOYEE') . '/worker';
        $this->client = new Client();
    }

    public function getWorker(Request $request)
    {
        try {
            $params = [];

            if($request->has('branch')) {
                $branchCode = $request->query('branch');
                if(!empty($branchCode)) $params['query']['branch'] = $branchCode;
            }

            if($request->has('bank')) {
                $bankId = $request->query('bank');
                if(!empty($bankId)) $params['query']['bank'] = $bankId;
            }

            if($request->has('status')) {
                $status = $request->query('status');
                if(!empty($status) || $status == 0) $params['query']['status'] = $status;
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

    public function storeWorker(Request $request)
    {
        try {
            $httpRequest = Http::asMultipart();

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

            $response = $httpRequest->post("$this->api", $request->all());

            return response()->json($response->json(), $response->status());

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function updateWorker(Request $request)
    {
        try {
            $httpRequest = Http::asMultipart();

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

            $response = $httpRequest->post("$this->api/update", $request->all());

            return response()->json($response->json(), $response->status());

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function deleteWorker(Request $request)
    {
        try {
            $responseData = $this->client->delete("$this->api", [
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

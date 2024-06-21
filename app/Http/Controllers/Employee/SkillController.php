<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_EMPLOYEE') . '/skill';
        $this->client = new Client();
    }

    public function index(Request $request)
    {
        try {
            $params = [];

            if($request->has('q')) {
                $q = $request->query('q');
                $params['query']['q'] = $q;
            }

            if($request->has('type')) {
                $type = $request->query('type');
                $params['query']['type'] = $type;
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

    public function getById($skillId)
    {
        try {
            $responseData = $this->client->get("$this->api/$skillId");
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

    public function store(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api", [
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

    public function update($skillId, Request $request)
    {
        try {
            $responseData = $this->client->put("$this->api/$skillId", [
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

    public function delete($skillId)
    {
        try {
            $responseData = $this->client->delete("$this->api/$skillId");
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

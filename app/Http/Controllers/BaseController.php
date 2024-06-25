<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected $apiEmployee;
    protected $apiGeocodingMaps;
    protected $mapsApiKey;
    protected $client;

    public function __construct()
    {
        $this->apiEmployee = env('URL_SERVICE_EMPLOYEE');
        $this->apiGeocodingMaps = env('URL_GEOCODING_API_MAPS');
        $this->mapsApiKey = env('MAPBOX_API_KEY');
        $this->client = new Client();
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
}

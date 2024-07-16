<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    private $api;
    private $client;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_ATTENDANCE') . '/holiday';
        $this->client = new Client();
    }

    public function getNationalHolidays($year)
    {
        try {
            $responseData = $this->client->get("$this->api/outside/$year");
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

    public function getHolidays()
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

    public function getHolidaysById($holidayId)
    {
        try {
            $responseData = $this->client->get("$this->api/$holidayId");
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

    public function getHolidaysByStatus($status)
    {
        try {
            $responseData = $this->client->get("$this->api/status/$status");
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

    public function storeHoliday(Request $request)
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

    public function deleteHoliday($holidayId)
    {
        try {
            $responseData = $this->client->delete("$this->api/$holidayId");
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

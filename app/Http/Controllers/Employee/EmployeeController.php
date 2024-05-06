<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class EmployeeController extends Controller
{
    private $api;
    private $client;
    private $jwtSecret;
    private $jwtAccessTokenExpired;
    private $jwtRefreshTokenExpired;
    private $configuration;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_EMPLOYEE');
        $this->jwtSecret = env('JWT_SECRET');
        $this->jwtAccessTokenExpired = env('JWT_ACCESS_TOKEN_EXPIRED');
        $this->jwtRefreshTokenExpired = env('JWT_REFRESH_TOKEN_EXPIRED');

        $this->configuration = Configuration::forSymmetricSigner(
                                new Sha256(),
                                InMemory::base64Encoded($this->jwtSecret)
                            );

        $this->client = new Client();
    }

    /*===========================
                AUTH
    ===========================*/

    public function register(Request $request)
    {
        try {
            $responseData = $this->client->post("$this->api/register", [
                'json'  => $request->all()
            ])->getBody()->getContents();

            $response = json_decode($responseData, true);

            return response()->json($response, 200);
        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }
}

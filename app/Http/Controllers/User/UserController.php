<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\Validator as JwtValidator;

class UserController extends Controller
{
    private $api;
    private $client;
    private $jwtSecret;
    private $jwtAccessTokenExpired;
    private $jwtRefreshTokenExpired;
    private $configuration;

    public function __construct()
    {
        $this->api = env('URL_SERVICE_USER');
        $this->jwtSecret = env('JWT_SECRET');
        $this->jwtAccessTokenExpired = env('JWT_ACCESS_TOKEN_EXPIRED');
        $this->jwtRefreshTokenExpired = env('JWT_REFRESH_TOKEN_EXPIRED');

        $this->configuration = Configuration::forSymmetricSigner(
                                new Sha256(),
                                InMemory::base64Encoded($this->jwtSecret)
                            );

        $this->client = new Client();
    }

    // LOGIN
    public function login(Request $request)
    {
        try {
            // cek validasi
            $validator = Validator::make($request->all(), [
                'email'     => 'required|email',
                'password'  => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // POST DATA
            $data = [
                'email'     => $request->email,
                'password'  => $request->password
            ];

            $responseData = $this->client->post("$this->api/login", [
                                'json' => $data,
                            ])->getBody()->getContents();

            $user = json_decode($responseData, true);

            // membuat token
            $now = new DateTimeImmutable();

            // access token
            $token = $this->configuration->builder()
                    ->issuedAt($now)
                    ->expiresAt($now->modify("+$this->jwtAccessTokenExpired day"))
                    ->withClaim('data', $user['data'])
                    ->getToken($this->configuration->signer(), $this->configuration->signingKey())
                    ->toString();

            // refresh token
            $refreshToken = $this->configuration->builder()
                            ->issuedAt($now)
                            ->expiresAt($now->modify("+$this->jwtRefreshTokenExpired day"))
                            ->withClaim('data', $user['data'])
                            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
                            ->toString();

            // save refresh token
            $this->client->post("$this->api/refresh-token", [
                'json' => [
                    'user_id'       => $user['data']['id'],
                    'refresh_token' => $refreshToken
                ],
            ]);

            return response()->json([
                'status'        => 'success',
                'code'          => 200,
                'message'       => 'OK',
                'data'          => [
                    'token'         => $token,
                    'refresh_token' => $refreshToken
                ]
            ], 200);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function logout(Request $request)
    {

        try {
            $user = $request->get('token_payload');

            $data = [
                'user_id'   => $user['id']
            ];

            $responseData = $this->client->post("$this->api/logout", [
                                'json'  => $data
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

    // REFRESH TOKEN
    public function refreshToken(Request $request)
    {

        try {
            // cek validasi
            $validator = Validator::make($request->all(), [
                'refresh_token' => 'required',
                'email'         => 'required|email'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }
            
            // cek refresh token
            $responseData = $this->client->get("$this->api/refresh-token/$request->refresh_token")->getBody()->getContents();
            $response = json_decode($responseData, true);

            $refreshToken = $response['data']['token'];
            $refreshTokenParse = $this->configuration->parser()->parse((string) $refreshToken);

            // cek kadaluwarsa token
            $currentTimestamp = time();
            $expirationTimestamp = $refreshTokenParse->claims()->get('exp')->getTimestamp();

            if ($expirationTimestamp !== null && $expirationTimestamp < $currentTimestamp) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'Refresh token expired'
                ], 400);
            }

            // cek email
            $tokenData = $refreshTokenParse->claims()->get('data');

            if($request->email != $tokenData['email']) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'email is not valid'
                ], 400);
            }

            // new access token
            $now = new DateTimeImmutable();
            $token = $this->configuration->builder()
                    ->issuedAt($now)
                    ->expiresAt($now->modify("+$this->jwtAccessTokenExpired day"))
                    ->withClaim('data', $tokenData)
                    ->getToken($this->configuration->signer(), $this->configuration->signingKey())
                    ->toString();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'token'     => $token
            ], 200);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    // GET USER
    public function getUsers(Request $request)
    {
        try {
            $responseData = $this->client->get("$this->api/user")->getBody()->getContents();
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

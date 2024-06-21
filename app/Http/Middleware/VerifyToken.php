<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\HttpFoundation\Response;

class VerifyToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Memeriksa apakah header Authorization ada dalam request
        if ($request->header('Authorization')) {
            // Mengambil nilai dari header Authorization
            $authorizationHeader = $request->header('Authorization');
            // Mengambil token dengan memisahkan "Bearer " dari header Authorization
            // $token = substr($authorizationHeader, 7);

            $configuration = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::base64Encoded(env('JWT_SECRET'))
            );

            $tokenParse = $configuration->parser()->parse((string) $authorizationHeader);

            // cek kadaluwarsa token
            $currentTimestamp = time();
            $expirationTimestamp = $tokenParse->claims()->get('exp')->getTimestamp();

            if ($expirationTimestamp !== null && $expirationTimestamp < $currentTimestamp) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 403,
                    'message'   => 'Token invalid'
                ], 403);
            }

            $request->attributes->add(['token_payload' => $tokenParse->claims()->get('data')]);

            // Menambahkan token ke dalam request sebagai atribut
            // $request->attributes->add(['api_token' => $token]);
        } else {
            return response()->json([
                'status'    => 'error',
                'code'      => 403,
                'message'   => 'Unauthorized'
            ], 403);
        }

        return $next($request);
    }
}

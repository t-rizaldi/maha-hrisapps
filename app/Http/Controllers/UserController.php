<?php

namespace App\Http\Controllers;

use App\Models\RefreshToken;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // LOGIN
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'         => 'required|email',
                'password'      => 'required'
            ]);

            // cek validasi request
            if ($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }
            // cek user
            $user = User::where('email', $request->email)->first();

            if(empty($user)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'email or password wrong'
                ], 400);
            }

            // cek invalid password
            $isValidPassword = Hash::check($request->password, $user->password);

            if(!$isValidPassword) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => 'email or password wrong'
                ], 400);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'role'      => $user->role
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // LOGOUT
    public function logout(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id'   => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            $userId = $request->user_id;
            $user = User::find($userId);

            if(empty($user)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'User not found'
                ], 200);
            }

            RefreshToken::where('user_id', $userId)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'Refresh token deleted'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // GET USER
    public function getUserById($id = null)
    {
        try {
            $user = User::find($id);

            if(empty($id) || empty($user)) {
                return response()->json([
                    'status'        => 'error',
                    'code'          => 204,
                    'message'       => 'User not found'
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'role'      => $user->role
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }


    // GET LIST USER
    public function getUsers()
    {
        try {
            $user = User::all();

            if(count($user) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'User not found'
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // GET REFRESH TOKEN
    public function getRefreshToken($rt = null)
    {
        try {
            $refreshToken = RefreshToken::where('token', $rt)->first();

            if(empty($refreshToken)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Invalid token'
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $refreshToken
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // STORE REFRESH TOKEN
    public function refreshTokenStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id'           => 'required',
                'refresh_token'     => 'required'
            ]);

            // cek validasi
            if($validator->fails()) {
                return response()->json([
                    'status'        => 'error',
                    'code'          => 400,
                    'message'       => $validator->errors()
                ], 400);
            }

            // cek user
            $user = User::find($request->user_id);

            if(empty($user)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'User not found'
                ], 200);
            }

            // create refresh token
            $createdRefreshToken = RefreshToken::create([
                'token'     => $request->refresh_token,
                'user_id'   => $request->user_id
            ]);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'id'        => $createdRefreshToken->id
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}

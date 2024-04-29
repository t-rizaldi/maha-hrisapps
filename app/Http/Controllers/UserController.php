<?php

namespace App\Http\Controllers;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // LOGIN
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required|email',
            'password'      => 'required'
        ]);

        // cek validasi request
        if ($validator->fails()) {
            return response()->json([
                'status'    => 'error',
                'message'   => $validator->errors()
            ], 400);
        }
        // cek user
        $user = User::where('email', $request->email)->first();

        if(empty($user)) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'email or password wrong'
            ], 400);
        }

        // cek invalid password
        $isValidPassword = Hash::check($request->password, $user->password);

        if(!$isValidPassword) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'email or password wrong'
            ], 400);
        }

        return response()->json([
            'status'    => 'success',
            'data'      => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role
            ]
        ], 200);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status'    => 'error',
                'message'   => $validator->errors()
            ], 400);
        }

        $userId = $request->user_id;
        $user = User::find($userId);

        if(empty($user)) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'User not found'
            ], 400);
        }

        RefreshToken::where('user_id', $userId)->delete();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Refresh token deleted'
        ], 200);
    }

    // GET USER
    public function getUserById($id = null)
    {
        $user = User::find($id);

        if(empty($id) || empty($user)) {
            return response()->json([
                'status'        => 'error',
                'message'       => 'User not found'
            ], 400);
        }

        return response()->json([
            'status'    => 'success',
            'data'      => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role
            ]
        ], 200);
    }


    // GET LIST USER
    public function getUsers()
    {
        $user = User::all();

        if(count($user) < 1) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'User not found'
            ], 400);
        }

        return response()->json([
            'status'    => 'success',
            'data'      => $user
        ], 200);
    }

    // GET REFRESH TOKEN
    public function getRefreshToken($rt = null)
    {
        $refreshToken = RefreshToken::where('token', $rt)->first();

        if(empty($refreshToken)) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Invalid token'
            ], 400);
        }

        return response()->json([
            'status'    => 'success',
            'data'      => $refreshToken
        ], 200);
    }

    // STORE REFRESH TOKEN
    public function refreshTokenStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required',
            'refresh_token'     => 'required'
        ]);

        // cek validasi
        if($validator->fails()) {
            return response()->json([
                'status'        => 'error',
                'message'       => $validator->errors()
            ], 400);
        }

        // cek user
        $user = User::find($request->user_id);

        if(empty($user)) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'User not found'
            ], 400);
        }

        // create refresh token
        $createdRefreshToken = RefreshToken::create([
            'token'     => $request->refresh_token,
            'user_id'   => $request->user_id
        ]);

        return response()->json([
            'status'    => 'success',
            'id'        => $createdRefreshToken->id
        ], 201);
    }
}

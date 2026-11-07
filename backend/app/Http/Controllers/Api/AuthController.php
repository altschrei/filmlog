<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user and return a JWT.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Authenticate an existing user and return a JWT.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user = auth('api')->user();

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'username' => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me()
    {
        $user = auth('api')->user();

        return response()->json([
            'id' => $user->id,
            'username' => $user->name,
            'email' => $user->email,
        ]);
    }
}

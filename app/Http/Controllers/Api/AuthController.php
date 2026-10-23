<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);
        $createdToken = $user->createToken('api-token');
        $token = $createdToken->plainTextToken;

        return response()->json(
            [
                'user' => $user,
                'token' => $token,
            ], 201
        );
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();
        if ($user && Hash::check($data['password'], $user->password)) {
            $createdToken = $user->createToken('api-token');
            $token = $createdToken->plainTextToken;

            return response()->json(
                [
                    'user' => $user,
                    'token' => $token,
                ], 200
            );
        } else {
            Log::warning('Login attempt failed', ['email' => $data['email'], 'ip' => $request->ip()]);

            return response()->json('Invalid credentials', 401);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}

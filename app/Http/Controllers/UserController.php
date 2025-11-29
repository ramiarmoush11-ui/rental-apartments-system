<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);
        $user = User::create($validatedData);
        return response()->json([
            'message' => 'account created successfully',
            'user' => $user
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();

        if (!Auth::attempt($validatedData->only('email', 'password'))) {
            return response()->json('invalied email or password', 401);
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Welcome back!',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'logout successfuly'], 200);
    }
    
}

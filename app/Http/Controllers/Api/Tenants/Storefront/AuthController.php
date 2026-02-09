<?php

namespace App\Http\Controllers\Api\Tenants\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new customer
     *
     * @requestMediaType application/json
     * @body array{name: "John Doe", email: "john@example.com", password: "password", phone: "12345678"}
     * @response array{success: true, message: "User registered successfully", token: "sanctum_token"}
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string'
        ]);

        $member = Member::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'code' => 'CUS-' . time(), // Simple code generation, can be improved
        ]);

        $token = $member->createToken('storefront', ['customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $member
        ], 201);
    }

    /**
     * Login customer
     *
     * @requestMediaType application/json
     * @body array{email: "john@example.com", password: "password"}
     * @response array{success: true, message: "Login successful", token: "sanctum_token"}
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $member = Member::where('email', $request->email)->first();

        if (! $member || ! Hash::check($request->password, $member->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $member->createToken('storefront', ['customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $member
        ]);
    }

    /**
     * Get authenticated customer profile
     *
     * @response Member
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    /**
     * Logout customer
     *
     * @response array{success: true, message: "Logged out"}
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }
}

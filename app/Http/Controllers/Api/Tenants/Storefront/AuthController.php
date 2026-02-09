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
     * @summary Register a new customer
     * @operationId storefront.auth.register
     * @tags Auth
     * 
     * @response 201 array{
     *   success: true,
     *   message: "User registered successfully",
     *   token: string,
     *   user: array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: ?string,
     *     code: string,
     *     created_at: string,
     *     updated_at: string
     *   }
     * }
     * 
     * @response 422 {"message":"The given data was invalid.","errors":{"email":["The email has already been taken."]}}
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
     * @summary Login customer
     * @operationId storefront.auth.login
     * @tags Auth
     * 
     * @response array{
     *   success: true,
     *   message: "Login successful",
     *   token: string,
     *   user: array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: ?string,
     *     code: string,
     *     created_at: string,
     *     updated_at: string
     *   }
     * }
     * 
     * @response 422 {"message":"The given data was invalid.","errors":{"email":["Las credenciales proporcionadas son incorrectas."]}}
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
     * @summary Get authenticated customer profile
     * @operationId storefront.auth.me
     * @tags Auth
     * 
     * @response array{
     *   success: true,
     *   data: array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: ?string,
     *     code: string,
     *     created_at: string,
     *     updated_at: string
     *   }
     * }
     * 
     * @response 401 {"message":"Unauthenticated."}
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
     * @summary Logout customer
     * @operationId storefront.auth.logout
     * @tags Auth
     * 
     * @response array{success: true, message: "Logged out"}
     * @response 401 {"message":"Unauthenticated."}
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

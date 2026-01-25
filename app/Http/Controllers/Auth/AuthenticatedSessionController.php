<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        /** @var \App\Models\Tenants\User $user */
        $user = $request->user();
        $token = $request->user()->createToken($user->getRememberTokenName());

        /**
         * @response array{
         *   success: true,
         *   message: "Yay! success to login",
         *   data: array{
         *     id: 1,
         *     is_owner: 1,
         *     name: "MICKEY GUDIEL REYES",
         *     email: "mickeyanthonygudiel@gmail.com",
         *     email_verified_at: null,
         *     created_at: "2026-01-24T08:59:01.000000Z",
         *     updated_at: "2026-01-24T23:19:37.000000Z",
         *     deleted_at: null,
         *     token: "10|1jegQGnlq0cC2ZBfv6ZDEX6WkNHkfHFoNCiJmEVNbb72cd9a",
         *     permissions: array("access feature flag", "approve purchasing", "others..."),
         *     features: array(purchasing: true, payment-method: true, "others...")
         *   }
         * }
         */
        return response()->json([
            'success' => true,
            'message' => 'Yay! success to login',
            'data' => array_merge($user->toArray(), [
                'token' => $token->plainTextToken,
                'permissions' => $user->roles()->first()->permissions()->where('guard_name', 'sanctum')->pluck('name')->toArray(),
                'features' => Feature::all(),
            ]),
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

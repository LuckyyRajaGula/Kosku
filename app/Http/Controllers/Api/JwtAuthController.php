<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        $user = Auth::guard('api')->user();
        if (($user->status_akun ?? 'Aktif') !== 'Aktif') {
            Auth::guard('api')->logout();

            return response()->json([
                'message' => 'Akun Anda nonaktif. Hubungi pemilik.',
            ], 403);
        }

        return $this->tokenResponse($token);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::guard('api')->user(),
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(JWTAuth::parseToken()->refresh());
    }

    public function logout(): JsonResponse
    {
        JWTAuth::parseToken()->invalidate();

        return response()->json([
            'message' => 'Logout berhasil. Token sudah di-blacklist.',
        ]);
    }

    private function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'user' => Auth::guard('api')->user(),
        ]);
    }
}

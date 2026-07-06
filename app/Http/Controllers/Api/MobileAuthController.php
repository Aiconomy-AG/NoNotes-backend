<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function register(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);

        return $this->tokenResponse($user, $credentials['device_name'] ?? null, 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->tokenResponse($user, $credentials['device_name'] ?? null);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, ?string $deviceName, int $status = 200)
    {
        $tokenName = filled($deviceName) ? trim($deviceName) : 'nonotes-mobile';

        return response()->json([
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => $user,
        ], $status);
    }
}

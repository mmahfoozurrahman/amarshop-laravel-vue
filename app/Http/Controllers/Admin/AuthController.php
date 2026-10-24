<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        $credentialsValid = $user
            && $user->isAdmin()
            && $user->is_active
            && $user->password !== null
            && Hash::check((string) $request->input('password'), $user->password);

        if (! $credentialsValid) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match any active admin account.',
            ]);
        }

        $token = $user->createToken('admin-'.$user->id, ['admin'])->plainTextToken;

        return response()->json([
            'message' => 'Welcome back, '.$user->name.'.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}

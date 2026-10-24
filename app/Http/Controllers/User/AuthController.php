<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, OtpService $otpService): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->input('phone'));
        $result = $otpService->request($phone, $request->ip());

        $payload = [
            'message' => "We sent a 6-digit OTP to {$phone}.",
            'expires_in' => OtpService::EXPIRY_MINUTES * 60,
        ];

        // Demo convenience: the OTP is echoed in debug mode instead of being sent by SMS.
        if (config('app.debug')) {
            $payload['demo_otp'] = $result['otp'];
        }

        return response()->json($payload);
    }

    public function verifyOtp(VerifyOtpRequest $request, OtpService $otpService, CartService $cart): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->input('phone'));

        $otpService->verify($phone, $request->input('otp'));

        $user = User::where('phone', $phone)->first();

        if ($user && ! $user->is_active) {
            abort(403, 'This account has been disabled. Please contact support.');
        }

        if (! $user) {
            $user = User::create([
                'name' => $request->filled('name')
                    ? $request->input('name')
                    : 'Customer '.substr($phone, -4),
                'phone' => $phone,
                'role' => 'customer',
                'is_active' => true,
            ]);
        }

        $token = $user->createToken('customer-'.$user->id, ['customer'])->plainTextToken;

        $cart->mergeGuestCart($user, $request->input('guest_token'));

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->loadCount(['orders', 'wishlists'])),
            'cart' => $cart->summary($user, null),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->loadCount(['orders', 'wishlists']));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}

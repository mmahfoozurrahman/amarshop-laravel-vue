<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const EXPIRY_MINUTES = 5;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_SECONDS = 60;

    /**
     * Create a fresh OTP for the phone number.
     *
     * @return array{otp: string, record: OtpVerification}
     */
    public function request(string $phone, ?string $ip = null): array
    {
        $latest = OtpVerification::where('phone', $phone)
            ->where('purpose', 'login')
            ->latest('id')
            ->first();

        if ($latest && $latest->created_at->gt(now()->subSeconds(self::RESEND_SECONDS))) {
            $wait = self::RESEND_SECONDS - (int) $latest->created_at->diffInSeconds(now());

            throw ValidationException::withMessages([
                'phone' => "Please wait {$wait} seconds before requesting another OTP.",
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        // Only the newest OTP per phone may be used.
        OtpVerification::where('phone', $phone)
            ->where('purpose', 'login')
            ->whereNull('verified_at')
            ->delete();

        $record = OtpVerification::create([
            'phone' => $phone,
            'otp_hash' => Hash::make($otp),
            'purpose' => 'login',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'ip_address' => $ip,
        ]);

        Log::info('OTP generated', ['phone' => $phone, 'otp' => $otp]);

        return ['otp' => $otp, 'record' => $record];
    }

    /**
     * Verify an OTP and mark it as used.
     */
    public function verify(string $phone, string $otp, string $purpose = 'login'): OtpVerification
    {
        $record = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => 'No OTP request was found for this phone number.',
            ]);
        }

        if ($record->isVerified()) {
            throw ValidationException::withMessages([
                'otp' => 'This OTP has already been used. Please request a new one.',
            ]);
        }

        if ($record->hasReachedAttemptLimit()) {
            throw ValidationException::withMessages([
                'otp' => 'Too many invalid attempts. Please request a new OTP.',
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'This OTP has expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => 'The OTP you entered is incorrect.',
            ]);
        }

        $record->forceFill(['verified_at' => now()])->save();

        return $record;
    }
}

<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', PhoneNumber::validationRule()],
            'otp' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:120'],
            'guest_token' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Bangladeshi mobile number (e.g. 01712345678).',
            'otp.digits' => 'The OTP must be exactly 6 digits.',
        ];
    }
}

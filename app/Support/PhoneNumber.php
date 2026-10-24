<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise a Bangladeshi phone number to the local 11-digit format.
     */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function validationRule(): string
    {
        return 'regex:/^(?:\+?880|0)1[3-9]\d{8}$/';
    }
}

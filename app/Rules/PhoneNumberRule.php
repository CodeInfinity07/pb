<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PhoneNumberRule implements Rule
{
    public function passes($attribute, $value)
    {
        // Basic phone number validation for Pakistani numbers
        // Accepts formats: +92-300-1234567, 03001234567, +923001234567
        return preg_match('/^(\+92[-\s]?|0)?[3][0-9]{2}[-\s]?[0-9]{7}$/', $value);
    }

    public function message()
    {
        return 'The :attribute must be a valid Pakistani phone number.';
    }
}
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContactNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (class_exists('App\Custom\Rules\ContactNumber')) {
            $customRule = new \App\Custom\Rules\ContactNumber;
            $customRule->validate($attribute, $value, $fail);

            return;
        }

        if (preg_match('/^\+?\d{4,15}$/', $value) == false) {
            $fail(__('validation.mobile_number'));
        }
    }
}

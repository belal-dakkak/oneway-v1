<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPhone implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$value) return false;

        // UAE: +971 5x xxx xxxx or 05x xxx xxxx
        $uaeRegex = '/^(?:\+971|00971|0)?5[02456]\d{7}$/';
        
        // Lebanon: +961 3 xxx xxx or +961 7x xxx xxx or 03 xxx xxx or 7x xxx xxx
        $lebanonRegex = '/^(?:\+961|00961|0)?(?:3|70|71|76|78|79|81)\d{6}$/';
        
        // Syria: +963 9xx xxx xxx or 09xx xxx xxx
        $syriaRegex = '/^(?:\+963|00963|0)?9[345689]\d{7}$/';

        // Remove spaces and dashes for checking
        $cleanValue = str_replace([' ', '-', '(', ')'], '', $value);

        return preg_match($uaeRegex, $cleanValue) || 
               preg_match($lebanonRegex, $cleanValue) || 
               preg_match($syriaRegex, $cleanValue);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid phone number from UAE, Lebanon, or Syria.';
    }
}

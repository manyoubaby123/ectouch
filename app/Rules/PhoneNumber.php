<?php

namespace App\Rules;

class PhoneNumber
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/^1(3[0-9]|4[0-9]|5[0-35-9]|6[6]|7[01345678]|8[0-9]|9[89])\d{8}$/', $value) ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.custom.mobile');
    }
}

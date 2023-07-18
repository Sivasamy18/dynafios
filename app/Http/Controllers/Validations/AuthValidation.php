<?php

namespace App\Http\Controllers\Validations;


class AuthValidation extends Validation
{
    public function validateLogin($data)
    {
        return $this->validate($data, array(
            'email' => 'email|required',
            'password' => 'required|between:8,20'
        ));
    }

    public function validateReminder($data)
    {
        return $this->validate($data, array('email' => 'email|required'));
    }
}

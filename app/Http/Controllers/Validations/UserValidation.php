<?php

namespace App\Http\Controllers\Validations;

class UserValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'email' => 'required|email|unique:users',
            'first_name' => 'required|between:3,15',
            'last_name' => 'required|between:3,15',
//            'title'  => 'required|between:3,15',
            'phone' => 'required|max:15'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'email' => 'email',
            'first_name' => 'required|between:3,15',
            'last_name' => 'required|between:3,15',
//            'title'        => 'required|between:3,15',
            'phone' => 'required|max:15',
            'new_password' => 'confirmed|between:8,20|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%&*]).*$/'
        ], array(
            'new_password.regex' => 'Password must be between 8 and 20 characters in length and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).'
        ));
    }

    public function validateRemind($data)
    {
        return $this->validate($data, [
            'password' => 'between:8,20|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%&*]).*$/'
        ], array(
            'password.regex' => 'Password must be between 8 and 20 characters in length and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).'
        ));
    }

    public function validatePassword($data)
    {
        return $this->validate($data, [
            'new_password' => 'confirmed|between:8,20|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%&*]).*$/'
        ], array(
            'new_password.regex' => 'Password must be between 8 and 20 characters in length and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).'
        ));
    }
}

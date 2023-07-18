<?php

namespace App\Http\Controllers\Validations;

use Validator;

abstract class Validation
{
    private $messages;
    private $failed;

    public function __construct()
    {
    }

    public function messages()
    {
        return $this->messages;
    }

    public function failed()
    {
        return $this->failed;
    }

    protected function validate($data, $rules, $messages = array())
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $this->messages = $validator->messages();
            $this->failed = $validator->failed();
            return false;
        }

        return true;
    }
}

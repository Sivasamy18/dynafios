<?php

namespace App\Http\Controllers\Validations;

class TicketMessageValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'body' => 'required|between:20,1000'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'body' => 'required|between:20,1000'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Validations;

class TicketValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'subject' => 'required|between:3,255',
            'body' => 'required|between:20,1000'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'subject' => 'required|between:3,255',
            'body' => 'required|between:20,1000'
        ]);
    }
}
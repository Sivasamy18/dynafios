<?php

namespace App\Http\Controllers\Validations;

class PaymentValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            "amount" => "required|regex:/\d{1,6}\.\d{2}/"
        ]);
    }
}

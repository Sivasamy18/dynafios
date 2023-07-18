<?php

namespace App\Http\Controllers\Validations;

class GenerateValidation extends Validation
{
    public function validateGenerate($data)
    {
        return $this->validate($data, [
            'start_date' => 'required|regex:/\d{2}\/\d{2}\/\d{4}/',
            'end_date' => 'required|regex:/\d{2}\/\d{2}\/\d{4}/'
        ]);
    }
}

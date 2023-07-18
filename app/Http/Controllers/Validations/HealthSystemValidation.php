<?php

namespace App\Http\Controllers\Validations;

class HealthSystemValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'health_system_name' => 'required|between:1,50|unique:health_system'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'health_system_name' => 'required|between:1,50|unique:health_system'
        ]);
    }
}

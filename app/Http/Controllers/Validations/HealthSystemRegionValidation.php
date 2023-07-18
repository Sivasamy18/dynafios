<?php

namespace App\Http\Controllers\Validations;

class HealthSystemRegionValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'region_name' => 'required|between:1,50'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            'region_name' => 'required|between:1,50'
        ]);
    }

    public function validateAddHospital($data)
    {
        return $this->validate($data, [
            'hospital' => 'required|integer'
        ]);
    }
}
